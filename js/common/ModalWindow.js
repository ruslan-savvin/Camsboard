//Savvin Ruslan 2024
import { reportStatus, getObjectId } from "/js/common/GeneralFunctions.js";
import { translate } from "/js/common/Localization.js";
import { DynamicForm } from "/js/common/DynamicForm.js";
import { api } from "/js/common/ApiClient.js";

export class modalWindow {
  #dropdown;
  #actionDropdownClick;

  constructor() {
    this.objectId = getObjectId();
    // A function from the module where the modal window was opened.
    // It is called to handle non-standard situations with select type fields.
    // If the module is not a class, define an anchor object (e.g., context = {};) and assign the callback as its method:
    //
    // context.callBackAction = function (data) {
    //   console.log("Callback triggered in the module! Received data:", data);
    // };
    //
    // Then link the context when initializing the modal:
    // const mW = new modalWindow();
    // mW.parent = context;

    this.parent = null;
    this.#dropdown = document.querySelector("#dropdown");
    this.#actionDropdownClick = (event) => this.#actionClick(event);
    this.#dropdown.addEventListener("click", this.#actionDropdownClick);
    this.fields = {};
    this.header = "";
    this.buttons = [];
    this.row = null;
    this.inPutData = {}; //data to fill input fields for editing
    this.outPutData = {}; //here we get the data after editing/filling in the input fields when closing the modal window
    this.action = "";
    this.modalWindowBody = null;
    this.extraParameter = null;
    Object.preventExtensions(this);
  }

  #destructor = async () => {
    this.#opacityLowerWindows("remove");
    await this.#slip();
    document.querySelector("#modalWindow" + this.objectId).remove();
    const elements = document.querySelectorAll(".modal-window");
    if (elements.length === 0) {
      document.querySelector("#containerModalWindow").remove();
    }
    this.#dropdown.removeEventListener("click", this.#actionDropdownClick);
    this.#actionDropdownClick = null;
  };

  setSettings = async () => {
    const modalTemplate = window.modalTemplate;
    const newModalTemplate = modalTemplate.replaceAll("%s", this.objectId);
    const template = document.createElement("template");
    template.innerHTML = newModalTemplate.trim();
    const element = template.content.firstElementChild;
    let container = document.querySelector("#containerModalWindow");
    if (container === null) {
      container = document.createElement("div");
      container.id = "containerModalWindow";
      container.className = "container-modal-window";
      document.body.appendChild(container);
    }
    container.appendChild(element);
    this.#opacityLowerWindows("add");
    const modalWindowBody = element.querySelector("#modalWindowBody" + this.objectId);
    this.modalWindowBody = modalWindowBody;
    this.#bodyGenerate(modalWindowBody);
    element.querySelector("#modalWindowHeader" + this.objectId).dataset.translate = this.header;
    const btnModalExecute = element.querySelector("#modalExecute" + this.objectId);
    const btnModalCancel = element.querySelector("#modalCancellation" + this.objectId);
    const btnModalAdditional = element.querySelector("#modalAdditional" + this.objectId);
    if (this.buttons.length === 1) {
      btnModalCancel.style.display = "none";
      btnModalExecute.dataset.translate = this.buttons[0];
      btnModalAdditional.style.display = "none";
    } else if (this.buttons.length === 2) {
      btnModalCancel.style.display = "block";
      btnModalExecute.dataset.translate = this.buttons[0];
      btnModalCancel.dataset.translate = this.buttons[1];
      btnModalAdditional.style.display = "none";
    } else if (this.buttons.length === 3) {
      btnModalExecute.dataset.translate = this.buttons[0];
      btnModalCancel.dataset.translate = this.buttons[1];
      btnModalAdditional.dataset.translate = this.buttons[2];
    } else {
      btnModalCancel.style.display = "none";
      btnModalExecute.style.display = "none";
      btnModalAdditional.style.display = "none";
    }
    await this.#translate();
    setTimeout(() => {
      element.classList.remove("modal-hidden-2");
    }, 50);
    this.#setFocus(this.modalWindowBody);
  };

  #translate = async () => {
    const modalWindow = document.querySelector("#modalWindow" + this.objectId);
    const newItems = modalWindow.querySelectorAll("[data-translate]");
    await translate(newItems);
  };

  #setFocus = (modalWindowBody) => {
    if (!modalWindowBody) return;
    const firstElement = modalWindowBody.querySelector("button.table-btn, input:not([disabled]), textarea:not([disabled])");
    if (firstElement) {
      firstElement.focus();
    } else {
      const btnModalExecute = modalWindowBody.parentElement.querySelector('[id^="modalExecute"]');
      btnModalExecute.focus();
    }
  };

  show = async () => {
    const execute = new Promise((resolve) => {
      document.querySelector("#modalExecute" + this.objectId).onclick = () => {
        if (this.#checkOutputData()) {
          this.#saveOutputDate();
          this.#destructor();
          resolve(this.outPutData);
        }
      };
      document.querySelector("#modalCancellation" + this.objectId).onclick = () => {
        this.#destructor();
        resolve(null);
      };
    });
    return await execute;
  };

  showFoto = async () => {
    const execute = new Promise((resolve) => {
      document.querySelector("#modalAdditional" + this.objectId).onclick = () => {
        this.#destructor();
        resolve();
      };
    });
    return await execute;
  };

  #checkOutputData = () => {
    const arrMes = ["modalWindow.inscription.fillRequiredFields", "\n"];
    for (let index1 in this.fields) {
      const field = this.fields[index1];
      if (!field.editorFieldNames) continue;
      for (const nameFieldDatabase in field.editorFieldNames) {
        const input = document.querySelector("#inputModal_" + nameFieldDatabase);
        if (field.editorSkipValidation && field.editorSkipValidation.includes(nameFieldDatabase)) {
          if (input) input.classList.remove("form-border-red");
          continue;
        }
        if (!input) continue;
        const val = input.value;
        const legend = input.closest("fieldset").querySelector(".legend");
        const baseText = legend.textContent.replace(/\*/g, "").trim();
        if (!val || Number(val) === 0 || val === "undefined") {
          if (field.allowZero && field.allowZero.includes(nameFieldDatabase) && val !== "") {
            continue;
          }
          const localizationId = field.editorFieldNames[nameFieldDatabase];
          arrMes.push(localizationId || nameFieldDatabase);
          arrMes.push("\n");
          legend.innerHTML = baseText + ' <span style="color: red;">***</span>';
        } else {
          legend.innerHTML = baseText;
        }
      }
    }
    if (arrMes.length > 2) {
      reportStatus(JSON.stringify(arrMes));
      return false;
    }
    return true;
  };

  #saveOutputDate = () => {
    this.outPutData = {};
    for (let column in this.fields) {
      const field = this.fields[column];
      if (!field.editorFieldNames) continue;
      for (const nameFieldDatabase in field.editorFieldNames) {
        const elementInput = document.querySelector(`#inputModal_${nameFieldDatabase}`);
        if (!elementInput) continue;
        const enumeration = field.enumeration;
        if (enumeration && enumeration[nameFieldDatabase]) {
          const dataInput = elementInput.dataset.translate;
          const key = Object.keys(enumeration[nameFieldDatabase]).find((k) => enumeration[nameFieldDatabase][k] === dataInput);
          this.outPutData[nameFieldDatabase] = Number(key);
        } else {
          const isInput = ["INPUT", "TEXTAREA"].includes(elementInput.tagName);
          let dataInput;
          if (isInput) {
            dataInput = elementInput.type === "number" ? elementInput.valueAsNumber || 0 : elementInput.value;
          } else {
            dataInput = elementInput.textContent;
          }
          this.outPutData[nameFieldDatabase] = dataInput;
        }
      }
    }
  };

  #slip = () => {
    return new Promise((resolve) => {
      document.querySelector("#modalWindow" + this.objectId).classList.add("modal-hidden-2");
      setTimeout(() => {
        resolve();
      }, 500);
    });
  };

  #opacityLowerWindows = (par) => {
    const elements = document.querySelectorAll(".modal-window");
    for (let index = 0; index < elements.length - 1; index++) {
      const element = elements[index];
      if (par === "add") {
        element.classList.add("modal-window-opacity");
      } else {
        element.classList.remove("modal-window-opacity");
      }
    }
    if (par !== "add") {
      this.#setFocus(elements[elements.length - 2]);
    }
  };

  #actionClick = async (event) => {
    const storage = event.target?.__menuStorage;
    if (!storage || storage.context !== this) return;
    const input = document.querySelector("#" + storage.menu.inputField);
    input.classList.remove("form-border-red");
    input.dataset.translate = event.target.dataset.translate;
    if (this.parent && this.parent.onModalResolve) {
      const data = { ...storage, ...{ value: event.target.dataset.translate, action: this.action } };
      this.parent.onModalResolve(data);
    }
    await this.#translate();
  };

  #bodyGenerate = (modalWindowBody) => {
    for (const column in this.fields) {
      const fieldsData = this.fields[column]?.nameFieldDatabase;
      if (!Array.isArray(fieldsData)) {
        continue;
      }
      const editorFields = this.fields[column]?.editorFieldNames;
      const editorType = this.fields[column]?.editorType;
      for (const nameField of fieldsData) {
        if (!editorFields || !(nameField in editorFields)) {
          continue;
        }
        if (!editorType || !(nameField in editorType)) {
          continue;
        }

        if (this.fields[column]?.availableForMenu !== undefined) {
          const availableMenu = this.fields[column]?.availableForMenu[nameField];
          if (!availableMenu || !availableMenu.includes(this.action)) continue;
        }

        const label = editorFields[nameField];
        const value = this.#getValue(this.row, column, nameField);
        const enumeration = this.#getEnumer(column, nameField);
        const link = this.#getLink(this.row, column);
        const type = editorType[nameField];

        if (type === "input text" || type === "input number" || type === "input date") {
          const fieldset = document.createElement("fieldset");
          fieldset.classList.add("fieldset", "fieldset-shadow");
          const legend = document.createElement("legend");
          legend.classList.add("legend");
          legend.dataset.translate = label;
          fieldset.append(legend);
          const input = document.createElement("input");
          input.id = "inputModal_" + nameField;
          input.classList.add("input-fieldset");
          input.value = value;
          if (type === "input text") {
            input.type = "text";
          } else if (type === "input number") {
            input.type = "number";
          } else if (type === "input date") {
            input.type = "date";
          }
          input.addEventListener("input", () => input.classList.remove("form-border-red"));
          fieldset.append(input);
          modalWindowBody.append(fieldset);
        } else if (type === "select") {
          let obMenu = {};
          Object.entries(enumeration).forEach(([enumId, enumLabel], index) => {
            obMenu[index] = {
              action: nameField,
              inputField: "inputModal_" + nameField,
              label: enumLabel,
              value: enumId,
            };
          });
          const fieldset = document.createElement("fieldset");
          fieldset.classList.add("fieldset", "fieldset-shadow");
          const legend = document.createElement("legend");
          legend.classList.add("legend");
          legend.dataset.translate = label;
          fieldset.append(legend);
          const div = document.createElement("div");
          div.classList.add("modal-button-input-row");
          fieldset.append(div);
          const button = document.createElement("button");
          button.classList.add("table-btn");
          button.dataset.row = this.row;
          button.textContent = "▼";
          button.__menuStorage = { context: this, menu: obMenu, settings: { type: "button", parent: null, children: [], isParentNav: false } };
          div.append(button);
          const input = document.createElement("input");
          input.id = "inputModal_" + nameField;
          input.classList.add("input-fieldset");
          input.dataset.translate = value;
          input.disabled = true;
          div.append(input);
          modalWindowBody.append(fieldset);
        } else if (type === "textarea") {
          const fieldset = document.createElement("fieldset");
          fieldset.classList.add("fieldset", "fieldset-shadow");
          const legend = document.createElement("legend");
          legend.classList.add("legend");
          legend.dataset.translate = label;
          fieldset.append(legend);
          const textarea = document.createElement("textarea");
          textarea.id = "inputModal_" + nameField;
          textarea.classList.add("input-fieldset");
          textarea.value = value;
          textarea.addEventListener("input", () => textarea.classList.remove("form-border-red"));
          fieldset.append(textarea);
          modalWindowBody.append(fieldset);
        } else if (type === "a") {
          const fieldset = document.createElement("fieldset");
          fieldset.classList.add("fieldset", "fieldset-shadow");
          const legend = document.createElement("legend");
          legend.classList.add("legend");
          legend.dataset.translate = label;
          fieldset.append(legend);
          const a = document.createElement("a");
          a.id = "inputModal_" + nameField;
          a.href = link;
          a.target = "_blank";
          a.dataset.translate = label;
          fieldset.append(a);
          modalWindowBody.append(fieldset);
        } else if (type === "p") {
          const div = document.createElement("div");
          div.dataset.translate = value;
          div.classList.add("modal-text-container");
          modalWindowBody.append(div);
        } else if (type === "img") {
          this.renderPhotoViewer(value);
        }
      }
    }
  };

  renderPhotoViewer = async (patch) => {
    const dataRequest = new DynamicForm();
    dataRequest.queryParams = {
      patch: patch,
      extraParameter: this.extraParameter[0],
    };
    dataRequest.callPhpLoader = ["/src/GatewayAuthorizedData.php", "nameFotos/authorization/fetch"];
    const loadingPromises = [dataRequest.load()];
    await api.safeLoad(loadingPromises);
    const photos = dataRequest.inPutData;

    let currentIndex = 0;
    const wrapper = document.createElement("div");
    wrapper.style.cssText = "display: flex; flex-direction: column; align-items: center; gap: 10px; width: 100%;";

    const img = document.createElement("img");
    img.style.cssText = "max-width: 100%; max-height: 70vh; object-fit: contain; border-radius: 4px; display: none;";

    const video = document.createElement("video");
    video.style.cssText = "max-width: 100%; max-height: 70vh; border-radius: 4px; display: none;";
    video.controls = true;
    video.autoplay = true;

    const updateViewer = async (index) => {
      if (photos.length === 0) return;
      const currentFile = photos[index];
      const isVideo = currentFile.name.toLowerCase().endsWith(".mp4");

      document.querySelector("#modalWindowHeader" + this.objectId).textContent = currentFile?.name ?? "";

      const mediaData = await this.loadPhoto(currentFile["path"]);
      const src = Array.isArray(mediaData) ? mediaData[0] : mediaData;

      if (isVideo) {
        img.style.display = "none";
        video.style.display = "block";
        video.src = src;
      } else {
        video.pause();
        video.removeAttribute("src");
        video.style.display = "none";
        img.style.display = "block";
        img.src = src;
      }
    };

    if (photos.length > 0) {
      await updateViewer(currentIndex);
    }

    const btnLeft = document.querySelector("#modalExecute" + this.objectId);
    const btnRight = document.querySelector("#modalCancellation" + this.objectId);

    btnLeft.addEventListener("click", async () => {
      if (photos.length === 0) return;
      currentIndex = (currentIndex - 1 + photos.length) % photos.length;
      await updateViewer(currentIndex);
    });

    btnRight.addEventListener("click", async () => {
      if (photos.length === 0) return;
      currentIndex = (currentIndex + 1) % photos.length;
      await updateViewer(currentIndex);
    });

    wrapper.append(img, video);
    this.modalWindowBody.append(wrapper);
  };

  loadPhoto = async (photoPath) => {
    const dataRequest = new DynamicForm();
    dataRequest.queryParams = {
      photoPath: photoPath,
    };
    dataRequest.callPhpLoader = ["/src/GatewayAuthorizedData.php", "loadFoto/authorization/fetch"];
    const loadingPromises = [dataRequest.load()];
    await api.safeLoad(loadingPromises);
    return dataRequest.inPutData[0];
  };

  #getLink = (row, column) => {
    const { link } = this.fields[column];
    if (!link || !this.inPutData?.[row]) return null;
    return this.inPutData[row][link] || null;
  };

  #getValue = (row, column, nameFieldDatabase) => {
    const value = this?.inPutData?.[row]?.[nameFieldDatabase];
    if (!value && value !== 0) return "";
    const enumeration = this.fields[column]?.["enumeration"]?.[nameFieldDatabase];
    if (!enumeration) return value;
    return enumeration[value] ?? value;
  };

  #getEnumer = (column, nameFieldDatabase) => {
    const enumeration = this.fields[column]?.["enumeration"]?.[nameFieldDatabase];
    if (!enumeration) return [];
    return enumeration;
  };
}
