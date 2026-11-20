//Savvin Ruslan 2024
import { getVarCss } from "/js/common/GeneralFunctions.js";
import { api } from "/js/common/ApiClient.js";
import { localStoragePath } from "/js/admin/Constants.js";
import { translate } from "/js/common/Localization.js";

const elementDropdown = document.querySelector("#dropdown");
const indentEdge = 1;

window.addEventListener(
  "scroll",
  () => {
    if (!elementDropdown.hasChildNodes()) return;
    nodesDelete(Array.from(elementDropdown.querySelectorAll("div")));
    removeProperty();
  },
  { capture: true },
);
document.addEventListener("click", function (event) {
  if (event.target.type === "checkbox") return;
  if (event.target.__menuStorage && event.target.__menuStorage?.menu[0]?.flatExecution) {
    handleNestedMenuClick(event);
    return;
  }
  let dropdownButton = event.target;
  if (!removeDropdownItems(dropdownButton)) return;
  addMenu(event, dropdownButton);
});

const handleNestedMenuClick = (event) => {
  const originalStorage = event.target.__menuStorage;
  const clonedStorage = { ...originalStorage };
  clonedStorage.menu = { ...originalStorage.menu[0] };
  clonedStorage.settings = { ...clonedStorage.settings, type: "menu", parent: event.target, isParentNav: false, children: [] };
  event.target.__menuStorage = clonedStorage;
  clonedStorage.context.actionClick(event);
  event.target.__menuStorage = originalStorage;
};

const removeDropdownItems = (dropdownButton) => {
  const storage = dropdownButton.__menuStorage;
  const isMenuAction = !storage || storage.settings.type === "menu"; // Trigger for full container cleanup
  resetRootStyles(dropdownButton, isMenuAction);
  const children = storage?.settings.children;

  const untouchables = new Set();
  const toDelete = [];

  // 1. If it's NOT a 'menu' action — build the protection chain (white list)
  if (!isMenuAction) {
    let current = dropdownButton;
    while (current) {
      untouchables.add(current);
      // Add siblings through parent
      const siblings = current.__menuStorage?.settings.parent?.__menuStorage?.settings.children;
      if (Array.isArray(siblings)) {
        siblings.forEach((s) => untouchables.add(s));
      }
      current = current.__menuStorage?.settings.parent;
    }
  }

  // 2. Identify elements for removal from the container
  const container = elementDropdown;
  if (container) {
    const allPresent = Array.from(container.children);
    allPresent.forEach((node) => {
      // If 'menu' — add EVERYTHING to the black list
      // Otherwise — only nodes not present in the white list
      if (isMenuAction || !untouchables.has(node)) {
        toDelete.push(node);
      }
    });
  }

  // 3. Execute removal algorithm
  nodesDelete(toDelete, dropdownButton);

  // 4. Determine next step:
  // If 'menu' — we just closed everything, nothing to build (false)
  // If node — return true if children array is empty (time to build)
  if (isMenuAction) {
    const burger = document.getElementById("menuburger");
    if (burger.checked) {
      if (burger.checked && !dropdownButton.closest(".menu-burger")) {
        burger.checked = false;
      }
    }
    return false;
  }
  return !Array.isArray(children) || children.length === 0;
};

const resetRootStyles = (dropdownButton) => {
  const storage = dropdownButton.__menuStorage;
  const isRoot = !storage?.settings.parent;
  const isMenuAction = !storage || storage?.settings.type === "menu";
  if (isRoot || isMenuAction) {
    removeProperty();
  }
};

const removeProperty = () => {
  document.querySelectorAll("nav > .nav-div").forEach((btn) => {
    btn.style.removeProperty("background-color");
    btn.style.removeProperty("--nav-bg");
  });
};

function nodesDelete(nodesToClose, dropdownButton) {
  const targets = [...document.querySelectorAll("[data-open]")];
  targets.forEach((div) => {
    const menuItems = div.__menuStorage?.settings.children;
    if (Array.isArray(menuItems)) {
      const hasMatch = menuItems.some((item) => nodesToClose.includes(item));

      if (hasMatch) {
        div.__menuStorage.settings.children = [];
        delete div.dataset.open;
      }
    }
  });
  handleCheckbox(nodesToClose, dropdownButton);
  nodesToClose.forEach((el) => el.classList.remove("dropdown-show"));
  setTimeout(() => {
    nodesToClose.forEach((el) => el.remove());
  }, 500);
}

// Refreshes the table data after the filter menu is closed
const handleCheckbox = (elements, dropdownButton) => {
  if (elements.length === 0) return;
  if (elements.some((el) => el.contains(dropdownButton))) return;
  const checkbox = elements[0].querySelector('input[type="checkbox"]');
  if (checkbox) {
    const table = elements[0].__menuStorage.context;
    const fakeEvent = {
      target: elements[0],
    };
    table.actionClick(fakeEvent);
  }
};

const addMenu = async (event, dropdownButton) => {
  dropdownButton.dataset.open = "";
  const itemsData = dropdownButton.__menuStorage.menu;
  for (let index in itemsData) {
    if (itemsData[index].inactive) continue;
    const item = itemsData[index];
    const div = document.createElement("div");
    div.classList.add("dropdown-content");
    const isMenuNode = item.menu !== undefined;
    const isParentNav = dropdownButton.classList.contains("nav-div") || !!dropdownButton.__menuStorage.settings?.isParentNav;
    if (isMenuNode) {
      div.__menuStorage = {
        context: dropdownButton.__menuStorage.context,
        menu: item.menu,
        settings: { type: "splitButton", isParentNav: isParentNav, parent: dropdownButton, children: [] },
      };
    } else {
      div.__menuStorage = {
        context: dropdownButton.__menuStorage.context,
        menu: item,
        settings: { type: "menu", isParentNav: isParentNav, parent: dropdownButton, children: [] },
      };
    }
    dropdownButton.__menuStorage.settings.children.push(div);
    let thereAreAdditionalElements = false;
    const backgroundImage = item.backgroundImage;
    const checkboxConfig = item.checkbox;
    if (checkboxConfig !== undefined) {
      thereAreAdditionalElements = true;
      const checkbox = document.createElement("input");
      checkbox.classList.add("checkbox-dropdown-content-transform-scale-1point5");
      checkbox.type = "checkbox";
      const checked = api.getLocalStorage(`${localStoragePath}:${item.storage}`);
      checkbox.checked = checked;
      div.__menuStorage.menu["checkboxNode"] = checkbox;
      div.append(checkbox);
      const div2 = document.createElement("div");
      setTextLocalizationParameter(itemsData, index, div2);
      div.append(div2);
    }
    if (backgroundImage !== undefined) {
      thereAreAdditionalElements = true;
      const div1 = document.createElement("div");
      div1.classList.add(backgroundImage);
      div.append(div1);
      if (checkboxConfig === undefined) {
        const div2 = document.createElement("div");
        setTextLocalizationParameter(itemsData, index, div2);
        div.append(div2);
      }
    }
    if (!thereAreAdditionalElements) {
      setTextLocalizationParameter(itemsData, index, div);
    }
    elementDropdown.append(div);
  }
  const newItems = dropdownButton.__menuStorage.settings.children;
  const itemsToTranslate = newItems
    .flatMap((item) => (item instanceof Node ? [item] : Object.values(item)))
    .map((el) => (el?.hasAttribute?.("data-translate") ? el : el?.querySelector?.("[data-translate]")))
    .filter(Boolean);
  await translate(itemsToTranslate);
  setColor(dropdownButton);
  if (Array.isArray(newItems)) {
    newItems.forEach((element) => {
      setTimeout(() => {
        element.classList.add("dropdown-show");
        const arrButtonQuarter = getButtonQuarter(dropdownButton);
        setWidth(dropdownButton);
        expandMenu(dropdownButton, arrButtonQuarter);
      }, 100);
    });
  }
};

const setColor = (dropdownButton) => {
  const type = dropdownButton.__menuStorage.settings.type;
  const newItems = dropdownButton.__menuStorage.settings.children;
  const step = getVarCss("--background-color-step");
  if (type === "button") {
    const baseColor = getDarkerColorFrom("--background-color-nav", step);
    dropdownButton.style.removeProperty("background-color");
    dropdownButton.style.setProperty("--nav-bg", baseColor);
    dropdownButton.style.backgroundColor;
  }
  const parentBg = dropdownButton.style.getPropertyValue("--nav-bg");
  for (const element of newItems) {
    const backgroundColor = element.__menuStorage?.settings.type === "splitButton" ? getDarkerColorFrom(dropdownButton, step) : parentBg;

    element.style.setProperty("--nav-bg", backgroundColor);
  }
};

const getLeftRightButtonCoordinate = (dropdownButton) => {
  const rect = dropdownButton.getBoundingClientRect();
  const parentBlock = dropdownButton.closest(".page");
  let left;
  let right;
  let center;
  if (parentBlock === null) {
    center = window.innerWidth / 2;
    left = rect.left;
    right = rect.right;
  } else {
    const rectShadowBlock = parentBlock.getBoundingClientRect();
    center = rectShadowBlock.width / 2;
    left = rect.left - rectShadowBlock.left;
    right = left + rect.width;
  }
  return {
    left: left,
    center: center,
    right: right,
  };
};
const getButtonQuarter = (dropdownButton) => {
  const leftRightButtonCoordinate = getLeftRightButtonCoordinate(dropdownButton);
  let horizontal;
  if (leftRightButtonCoordinate.left <= leftRightButtonCoordinate.center && leftRightButtonCoordinate.right <= leftRightButtonCoordinate.center) {
    horizontal = "left";
  } else if (
    leftRightButtonCoordinate.left <= leftRightButtonCoordinate.center &&
    leftRightButtonCoordinate.right >= leftRightButtonCoordinate.center
  ) {
    horizontal = "left-right";
  } else if (
    leftRightButtonCoordinate.left >= leftRightButtonCoordinate.center &&
    leftRightButtonCoordinate.right >= leftRightButtonCoordinate.center
  ) {
    horizontal = "right";
  }
  if (dropdownButton.dataset.menuPosition === "rightException") {
    horizontal = "rightException";
  }
  const rect = dropdownButton.getBoundingClientRect();
  let vertical;
  const windowHeightCenter = window.innerHeight / 2;
  if (rect.top <= windowHeightCenter && rect.bottom <= windowHeightCenter) {
    vertical = "top";
  } else if (rect.top <= windowHeightCenter && rect.bottom >= windowHeightCenter) {
    vertical = "top-bottom";
  } else if (rect.top >= windowHeightCenter && rect.bottom >= windowHeightCenter) {
    vertical = "bottom";
  }
  return [horizontal, vertical];
};
const setWidth = (dropdownButton) => {
  const parentBlock = dropdownButton.closest(".page");
  const edgeOffset = remToPx(indentEdge);
  const windowRightSide = parentBlock === null ? window.innerWidth - edgeOffset : parentBlock.getBoundingClientRect().width - edgeOffset * 2;

  const elements = document.querySelectorAll(".dropdown-show");
  let maxWidth = 0;
  for (let element of elements) {
    const currentWidth = element.offsetWidth;
    if (currentWidth > maxWidth) {
      maxWidth = currentWidth;
    }
  }
  if (maxWidth > windowRightSide) {
    maxWidth = windowRightSide;
  }
  const menuburger = document.querySelector("#menuburger").checked;
  const menustorage = dropdownButton.__menuStorage;
  const isParentNav = menustorage.settings.isParentNav === true;
  for (let element of elements) {
    if (isParentNav && menuburger === true) {
      element.style.width = "100%";
    } else {
      element.style.width = maxWidth + "px";
    }
  }
};
const expandMenu = (btn, arrButtonQuarter) => {
  const targetClasses = ["nav-div", "dropdown-content", "table-btn", "standard-btn"];
  const isTarget = targetClasses.some((cls) => btn.classList.contains(cls));
  const dropdownButton = isTarget ? btn : btn.parentElement;

  const newItems = dropdownButton.__menuStorage.settings.children;
  const BoundingClientRect = dropdownButton.getBoundingClientRect();
  const elements = document.querySelectorAll(".dropdown-show");
  const menuburger = document.querySelector("#menuburger").checked;
  const menustorage = dropdownButton.__menuStorage;
  const isParentNav = menustorage.settings.isParentNav === true;
  if (isParentNav && menuburger === true) {
    let y = BoundingClientRect.bottom;
    for (let element of newItems) {
      const height = element.getBoundingClientRect().height;
      element.style.top = y + "px";
      y = y + height;
      element.style.left = 0 + "px";
    }
    return;
  } else if (isParentNav && menuburger === false) {
    let y = BoundingClientRect.bottom;
    const x = BoundingClientRect.left;
    const width = BoundingClientRect.width;
    for (let index in newItems) {
      const element = newItems[index];
      const height = element.getBoundingClientRect().height;
      element.style.top = y + "px";
      y = y + height;
      element.style.left = x + "px";
      if (width > element.offsetWidth) {
        element.style.width = width + "px";
      }
    }
    return;
  }
  if (arrButtonQuarter[1] === "top") {
    let y = BoundingClientRect.bottom;
    for (let element of newItems) {
      const height = element.getBoundingClientRect().height;
      element.style.top = y + "px";
      y = y + height;
    }
  } else if (arrButtonQuarter[1] === "bottom") {
    let y = BoundingClientRect.top;
    let height = 0;
    for (let element of newItems) {
      height = height + element.getBoundingClientRect().height;
    }
    y = y - height;
    for (let element of newItems) {
      const height = element.getBoundingClientRect().height;
      element.style.top = y + "px";
      y = y + height;
    }
  } else if (arrButtonQuarter[1] === "top-bottom") {
    let y = BoundingClientRect.top;
    let height = 0;
    for (let element of newItems) {
      height = height + element.getBoundingClientRect().height;
    }
    y = y - height / 2;
    for (let element of newItems) {
      const height = element.getBoundingClientRect().height;
      element.style.top = y + "px";
      y = y + height;
    }
  }
  if (arrButtonQuarter[0] === "left") {
    const windowRightSide = window.innerWidth;
    const buttonRight = BoundingClientRect.right;
    const standToRightEdge = windowRightSide - buttonRight;
    for (let element of elements) {
      const elementWidth = element.getBoundingClientRect().width;
      if (standToRightEdge - elementWidth > remToPx(indentEdge)) {
        element.style.left = buttonRight + "px";
      } else {
        element.style.left = windowRightSide - elementWidth - remToPx(indentEdge) + "px";
      }
    }
  } else if (arrButtonQuarter[0] === "rightException") {
    const buttonRight = BoundingClientRect.right;
    for (let element of elements) {
      const elementWidth = element.getBoundingClientRect().width;
      element.style.left = buttonRight - elementWidth + "px";
    }
  } else if (arrButtonQuarter[0] === "right") {
    const buttonLeft = BoundingClientRect.left;
    for (let element of elements) {
      const elementWidth = element.getBoundingClientRect().width;
      if (buttonLeft - elementWidth > remToPx(indentEdge)) {
        element.style.left = buttonLeft - elementWidth + "px";
      } else {
        element.style.left = remToPx(indentEdge) + "px";
      }
    }
  } else if (arrButtonQuarter[0] === "left-right") {
    const widthButtonCenter = BoundingClientRect.left + BoundingClientRect.width / 2;
    for (let element of elements) {
      const widthElement = element.getBoundingClientRect().width;
      element.style.left = widthButtonCenter - widthElement / 2 + "px";
    }
  }
};

const remToPx = (remValue) => {
  const rootFontSize = getComputedStyle(document.documentElement).fontSize;
  return remValue * parseFloat(rootFontSize);
};

const setTextLocalizationParameter = (obAtributDynamicDropdown, index, element) => {
  element.dataset.translate = obAtributDynamicDropdown[index]["label"];
};

const getDarkerColorFrom = (input, amount = 5) => {
  let colorStr = input;
  if (input instanceof HTMLElement) {
    const style = getComputedStyle(input);
    colorStr = style.getPropertyValue("--nav-bg").trim() || style.backgroundColor;
  } else if (typeof input === "string" && input.startsWith("--")) {
    colorStr = getComputedStyle(document.documentElement).getPropertyValue(input).trim();
  }
  const ctx = document.createElement("canvas").getContext("2d");
  ctx.fillStyle = colorStr;
  const rgbStr = ctx.fillStyle;
  const r = parseInt(rgbStr.substring(1, 3), 16);
  const g = parseInt(rgbStr.substring(3, 5), 16);
  const b = parseInt(rgbStr.substring(5, 7), 16);
  let rN = r / 255,
    gN = g / 255,
    bN = b / 255;
  let max = Math.max(rN, gN, bN),
    min = Math.min(rN, gN, bN);
  let l = (max + min) / 2;
  let h, s;
  if (max === min) {
    h = s = 0;
  } else {
    let d = max - min;
    s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
    if (max === rN) h = (gN - bN) / d + (gN < bN ? 6 : 0);
    else if (max === gN) h = (bN - rN) / d + 2;
    else h = (rN - gN) / d + 4;
    h /= 6;
  }
  l = Math.max(0, l - amount / 100);
  const f = (n) => {
    const k = (n + h * 12) % 12;
    const a = s * Math.min(l, 1 - l);
    const result = l - a * Math.max(-1, Math.min(k - 3, 9 - k, 1));
    return Math.round(result * 255);
  };
  return `rgb(${f(0)}, ${f(8)}, ${f(4)})`;
};
