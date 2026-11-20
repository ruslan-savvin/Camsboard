//Savvin Ruslan 2024
import { StructureMainMenu } from "/js/admin/structureOfTables/StructureMainMenu.js";
import { localStoragePathGeneral, pageVideo } from "/js/admin/Constants.js";
import { api } from "/js/common/ApiClient.js";
import { CommonData } from "/js/admin/CommonData.js";
import { getModuleName, reportStatus } from "/js/common/GeneralFunctions.js";
import { TableManagement } from "/js/common/TableManagement.js";
import { translate } from "/js/common/Localization.js";

const context = {};
const moduleName = getModuleName(import.meta.url);
document.querySelector("#" + moduleName).onload = async function () {
  context["objectLanguages"] = CommonData.getData("objectLanguages");
  context["objectDevice"] = CommonData.getData("objectDevice");
  const navButtons = [];
  const button1 = document.querySelector("#buttonNav1");
  const button2 = document.querySelector("#buttonNav2");
  const button3 = document.querySelector("#buttonNav3");
  const adminOutput = document.querySelector("#adminOutput");
  if (button1) navButtons.push(button1);
  if (button2) navButtons.push(button2);
  if (button3) navButtons.push(button3);
  if (adminOutput) navButtons.push(adminOutput);

  const structureMainMenu = new StructureMainMenu();
  button1.__menuStorage = structureMainMenu.init(1, context);
  button2.__menuStorage = structureMainMenu.init(2, context);
  button3.__menuStorage = structureMainMenu.init(3, context);

  button3.querySelectorAll("*").forEach((el) => {
    el.__menuStorage = button3.__menuStorage;
  });
  applyResponsiveStyles(navButtons);
  const dropdown = document.querySelector("#dropdown");
  dropdown.addEventListener("click", (e) => actionClick(e));

  const newItems = document.querySelector("nav").querySelectorAll("[data-translate]");
  await translate(newItems);
  const hiddenElements = document.querySelectorAll(".nav-div-hidden");
  hiddenElements.forEach((el, index) => {
    el.classList.remove("nav-div-hidden");
    el.style.transitionDelay = `${index * 0.1}s`;
  });
};

const applyResponsiveStyles = (buttons) => {
  const vw = document.documentElement.clientWidth;
  const rootStyle = getComputedStyle(document.documentElement);
  const breakpoint = parseInt(rootStyle.getPropertyValue("--media-max-width")) || 990;
  buttons.forEach((btn, index) => {
    if (vw <= breakpoint) {
      btn.style.top = `calc(var(--header-h) + ${index} * var(--nav-item-h))`;
      btn.classList.add("dropdown-content", "dropdown-content-nav");
      btn.classList.remove("nav-div");
      btn.style.setProperty("--nav-bg", "var(--background-color-nav)");
    } else {
      btn.classList.remove("dropdown-content", "dropdown-content-nav");
      btn.classList.add("nav-div");
      btn.style.top = "";
    }
  });
};
const actionClick = async (event) => {
  const storage = event.target.__menuStorage ? event.target.__menuStorage : event.target.parentElement.__menuStorage;
  if (!storage || storage.context !== context) return;
  if (storage.settings.type === "splitButton") return;
  const menu = storage.menu;
  if (menu.action === "changeDevices") {
    window.deviceId = menu.value;
    // const { eventInit } = await import("/js/admin/Events.js");
    // eventInit(true);
    const { videoInit } = await import("/js/admin/Video.js");
    videoInit(true);
    const { checkStatus } = await import("/js/admin/Main.js");
    checkStatus();
    return;
  }
  if (menu.action === "changeLanguage") {
    changeLanguage(menu.value);
    return;
  }
  const page = document.querySelector("#" + menu.action);
  const elements = document.querySelectorAll(".page");
  for (const el of elements) {
    if (window.column > 1 && el.id === pageVideo) {
      continue;
    }
    el.classList.add("page-hidden");
  }
  page.classList.remove("page-hidden");
};

const changeLanguage = (id) => {
  Object.values(context["objectLanguages"]).forEach((item) => {
    document.querySelector("#buttonNav31").classList.remove(`flag-${item.flag}`);
  });
  const flag = context["objectLanguages"][id]?.flag;
  document.querySelector("#buttonNav31").classList.add(`flag-${flag}`);
  window.language = id;
  translate(document.querySelectorAll("[data-translate]"));
};

document.querySelector("#adminOutput").onclick = async function () {
  const data = {
    action: "user/outputSession",
  };
  const loadingPromises1 = [api.send(data, "/src/GatewayAuthorizedData.php")];
  await api.safeLoad(loadingPromises1);
  window.location.reload();
};

window.addEventListener("visibilitychange", function () {
  api.setLocalStorage(localStoragePathGeneral + ":filters:deviceId", window.deviceId);
  api.setLocalStorage(localStoragePathGeneral + ":filters:language", window.language);
  api.setLocalStorage(localStoragePathGeneral + ":filters:pages", "");
  const elements = document.querySelectorAll(".page:not(.page-hidden)");
  for (let element of elements) {
    api.setLocalStorage(localStoragePathGeneral + ":filters:pages:" + element.id, true);
  }
});

window.addEventListener("beforeunload", function (event) {
  const hasUnsavedChanges = TableManagement.tableStore.some((tableInstance) => tableInstance.isChange === true);
  if (hasUnsavedChanges) {
    const localizedItemsWithNewLines = TableManagement.tableStore
      .filter((tableInstance) => tableInstance.isChange === true)
      .flatMap((tableInstance) => ["→ ", `global.nav.${tableInstance.nodeTable.parentElement.id}`, "\n"]);
    reportStatus(["⚠️ ", "global.message", "\n", ...localizedItemsWithNewLines]);

    event.preventDefault();
    event.returnValue = "";
  }
});
