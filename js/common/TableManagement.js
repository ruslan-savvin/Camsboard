//Savvin Ruslan  2025
import { localStoragePath, tableAttachmentPoint, numberOfRowDefault, menuNumberOfLines } from "/js/admin/Constants.js";
import { modalWindow } from "/js/common/ModalWindow.js";
import { lazyImagesStart } from "/js/admin/LazyLoading.js";
import { reportStatus, getObjectId, formatDataValue } from "/js/common/GeneralFunctions.js";
import { api } from "/js/common/ApiClient.js";
import { translate } from "/js/common/Localization.js";

export class TableManagement {
  static tableStore = [];
  #dropdown;
  #actionDropdownClick;

  constructor() {
    this.objectId = "";
    TableManagement.tableStore.push(this);
    this.nodeTable = null;
    this.parentCell = null;
    this.#dropdown = document.querySelector("#dropdown");
    this.#actionDropdownClick = (event) => this.actionClick(event);
    this.#dropdown.addEventListener("click", this.#actionDropdownClick);
    this.callBackAction = null;
    this.callbackCellRender = null;
    this.refresh = true;
    this.additionalInfo = null;
    this.queryParams = null;
    this.tableView = "row";
    this.callPhpLoader = "";
    this.callPhpSave = "";
    this.inPutData = {};
    this.inPutDataOrigenal = {};
    this.tableFields = {};
    this.menuFilter = "";
    this.createSearchPanel = true;
    this.buttonNumerLines = null;
    this.searchPanel = null;
    this.formData = false;
    this.btnIsPinned = false;
    this.isChange = false;
    this.saveButtonMenu = null;
    this.savingSettings = null;
    this.topOffset = 0;
    Object.preventExtensions(this);
  }

  destructor = () => {
    if (!this.nodeTable) return;
    const nestedTables = this.nodeTable.querySelectorAll(".table-display-grid");
    for (let element of nestedTables) {
      const tablObject = element.__tablObject;
      tablObject.#dropdown.removeEventListener("click", tablObject.#actionDropdownClick);
      tablObject.#actionDropdownClick = null;
      tablObject.nodeTable.__tablObject = null;
      tablObject.nodeTable = null;
      const index = TableManagement.tableStore.findIndex((item) => item.objectId === tablObject.objectId);
      TableManagement.tableStore.splice(index, 1);
    }
    this.#dropdown.removeEventListener("click", this.#actionDropdownClick);
    this.#actionDropdownClick = null;
    this.nodeTable.remove();
    this.nodeTable.__tablObject = null;
    const index = TableManagement.tableStore.findIndex((item) => item.objectId === this.objectId);
    TableManagement.tableStore.splice(index, 1);
  };

  loadDataAndRefresh = () => {
    this.refresh = true;
    this.loadData();
  };

  async loadData() {
    const queryParamsCopy = {
      ...this.queryParams,
      where: this.#getWhere(),
      limit: this.#getLimit(),
    };
    const [phpFile, action] = this.callPhpLoader;
    const data = {
      action: action,
      queryParams: queryParamsCopy,
    };
    const result = await api.send(data, phpFile);
    this.setInPutData(result.data);
    (await this.refresh) ? await this.refreshTable() : await this.createTable();
    return true;
  }

  #getWhere = () => {
    let whereStr = this.queryParams?.where || "";
    const placeholders = whereStr.match(/\{DATA_HERE\/[\w.]+\}/g);
    if (!placeholders) return whereStr;
    placeholders.forEach((placeholder) => {
      const [, partPlaceholder] = placeholder.replace(/[{}]/g, "").split("/");
      const [, column] = partPlaceholder.split(".");
      const storageKey = `${localStoragePath}:${this.objectId}:filters:${column}`;
      const storageData = api.getLocalStorage(storageKey) || {};
      const ids = Object.keys(storageData)
        .filter((id) => storageData[id] === true)
        .join(",");
      const replacement = ids ? `IN (${ids})` : ` = ${partPlaceholder}`;
      whereStr = whereStr.replace(placeholder, replacement);
    });
    return whereStr;
  };

  #getLimit = () => {
    const storageKey = `${localStoragePath}:${this.objectId}:filters:numberOfRow`;
    return "LIMIT " + (api.getLocalStorage(storageKey) || numberOfRowDefault);
  };

  #triggerCallback = (action) => {
    if (typeof this.callBackAction === "function") {
      this.callBackAction({ menu: { action }, context: this });
    }
  };

  setInPutData = (inPutData) => {
    this.inPutData = inPutData;
    this.inPutDataOrigenal = structuredClone(this.inPutData);
  };

  setTableNode = (objectId, parentCell) => {
    this.objectId = objectId ?? getObjectId();
    if (objectId === null) {
      const nodeTable = document.createElement("div");
      nodeTable.id = `table${this.objectId}`;
      nodeTable.classList.add("table-display-grid");
      this.nodeTable = nodeTable;
      parentCell.appendChild(nodeTable);
      this.parentCell = parentCell;
    } else {
      this.nodeTable = document.querySelector(`#${tableAttachmentPoint}${this.objectId}`);
    }
    this.nodeTable.__table = this;
  };

  #createSearchPanel = () => {
    const table = this.nodeTable;
    table.dataset.table = "";
    const div = document.createElement("div");
    div.classList.add("table-panel-header");
    const input = document.createElement("input");
    this.searchPanel = input;
    input.classList.add("table-panel-header-input");
    div.append(input);
    const buttonSearch = document.createElement("button");
    buttonSearch.classList.add("table-btn", "table-panel-header-button-search-background-image");
    buttonSearch.__menuStorage = {
      context: this,
      menu: { action: "setTextFilter" },
      settings: { type: "menu", parent: null, children: [], isParentNav: false },
    };
    // Handle click events for the search button
    // Trigger the search button click when the user presses
    //  the Enter key in the input field
    buttonSearch.addEventListener("click", this.#actionDropdownClick);
    div.append(buttonSearch);
    input.addEventListener("keydown", (event) => {
      if (event.key === "Enter") {
        event.preventDefault();
        buttonSearch.click();
      }
    });
    const buttonClearSearch = document.createElement("button");
    buttonClearSearch.classList.add("table-btn", "table-panel-header-button-cross-button-background-image");
    buttonClearSearch.__menuStorage = {
      context: this,
      menu: { action: "resetTextFilter" },
      settings: { type: "menu", parent: null, children: [], isParentNav: false },
    };
    buttonClearSearch.addEventListener("click", this.#actionDropdownClick);
    div.append(buttonClearSearch);
    const menuHeaderTemplate1 = {};
    for (let index in menuNumberOfLines) {
      menuHeaderTemplate1[index] = {};
      menuHeaderTemplate1[index]["action"] = "numberOfRow";
      menuHeaderTemplate1[index]["value"] = menuNumberOfLines[index][0];
      menuHeaderTemplate1[index]["label"] = menuNumberOfLines[index][1];
      menuHeaderTemplate1[index]["objectId"] = this.objectId;
    }
    const button = document.createElement("button");
    this.buttonNumerLines = button;
    button.classList.add("table-btn");
    button.__menuStorage = { context: this, menu: menuHeaderTemplate1, settings: { type: "button", parent: null, children: [], isParentNav: false } };
    button.dataset.menuPosition = "rightException";
    let numberOfRow = api.getLocalStorage(`${localStoragePath}:${this.objectId}:filters:numberOfRow`);
    if (!numberOfRow) {
      numberOfRow = numberOfRowDefault;
    }
    button.textContent = menuNumberOfLines.find((item) => item[0] == numberOfRow)[1];
    div.append(button);
    const button2 = document.createElement("button");
    button2.classList.add("table-btn", "table-panel-header-button-filter-background-image");
    button2.dataset.menuPosition = "rightException";
    button2.__menuStorage = { context: this, menu: this.menuFilter, settings: { type: "button", parent: null, children: [], isParentNav: false } };
    div.append(button2);
    table.append(div);
  };

  #createHeader = () => {
    const tableView = api.getLocalStorage(`${localStoragePath}:${this.objectId}:filters:tableView`);
    if (tableView) {
      this.tableView = tableView;
    }
    const table = this.nodeTable;
    const buttonSticky = document.createElement("button");
    buttonSticky.classList.add("table-btn", "table-panel-header-button-sticky-background-image");
    buttonSticky.__menuStorage = {
      context: this,
      menu: { action: "pinned" },
      settings: { type: "menu", parent: null, children: [], isParentNav: false },
    };
    buttonSticky.addEventListener("click", this.#actionDropdownClick);
    this.btnIsPinned = api.getLocalStorage(`${localStoragePath}:${this.objectId}:filters:isPinned`) ?? false;
    this.#isPinned(buttonSticky);
    const buttonSettings = document.createElement("button");
    buttonSettings.classList.add("table-btn", "table-panel-header-button-settings-background-image");
    const menu = {
      0: {
        action: "viewingMode",
        value: "row",
        label: "Rows",
      },
      1: {
        action: "viewingMode",
        value: "cards",
        label: "Cards",
      },
    };
    buttonSettings.__menuStorage = { context: this, menu, settings: { type: "button", parent: null, children: [], isParentNav: false } };
    for (let column in this.tableFields) {
      if (this.tableFields[column].type === "button") {
        const div = document.createElement("div");
        div.classList.add("table-cell", "table-cell-padding", "table-cell-row", "table-header-cell");
        div.dataset.column = column;
        if (this.tableFields[column].menuHeader !== undefined) {
          const button = document.createElement("button");
          if (this.tableFields[column].id !== undefined) {
            button.id = this.tableFields[column].id;
          }
          button.classList.add("table-btn");
          button.__menuStorage = {
            context: this,
            menu: this.tableFields[column].menuHeader,
            settings: { type: "button", parent: null, children: [], isParentNav: false },
          };
          button.textContent = "▼";
          if (column === "0") {
            div.append(buttonSticky);
            div.append(buttonSettings);
          }
          div.append(button);
          const divSimbol = document.createElement("div");
          div.append(divSimbol);
          const simbolSave = document.createElement("div");
          simbolSave.classList.add("table-unsaved-badge");
          divSimbol.append(simbolSave);
          this.saveButtonMenu = divSimbol;
        }
        table.append(div);
      } else if (
        this.tableFields[column].type === "text" ||
        this.tableFields[column].type === "line number" ||
        this.tableFields[column].type === "table" ||
        this.tableFields[column].type === "img" ||
        this.tableFields[column].type === "a"
      ) {
        const div = document.createElement("div");
        div.classList.add("table-cell", "table-header-cell");
        div.dataset.column = column;
        if (this.tableView === "row") {
          div.dataset.translate = this.tableFields[column].columnName;
        }
        table.append(div);
      }
    }
    table.style.gridTemplateColumns = this.#setGridColumns();

    const panelHeader = this.nodeTable.querySelector(".table-panel-header");
    if (panelHeader) {
      const headerHeight = panelHeader.offsetHeight;
      const headerCells = this.nodeTable.querySelectorAll(".table-header-cell");
      headerCells.forEach((cell) => {
        cell.style.top = `${headerHeight}px`;
      });
    }
  };

  createTable = async () => {
    this.#triggerCallback("onBeforeTableCreate");
    this.nodeTable.__tablObject = this;
    if (this.createSearchPanel) {
      this.#createSearchPanel();
    }
    this.#createHeader();
    if (this.tableView === "row") {
      await this.#refreshTableRow();
    } else if (this.tableView === "cards") {
      await this.#refreshTableCards();
    }
    this.#triggerCallback("onAfterTableCreate");
  };

  refreshTable = async () => {
    this.#triggerCallback("onBeforeTableCreate");
    if (this.tableView === "row") {
      await this.#refreshTableRow();
    } else if (this.tableView === "cards") {
      await this.#refreshTableCards();
    }
    this.#triggerCallback("onAfterTableCreate");
    const newItems = this.nodeTable.querySelectorAll("[data-translate]");
    await translate(newItems);
    this.#applyHighlighting();
  };

  #changeViewTable = async () => {
    this.nodeTable.querySelectorAll(".table-cell, .container-card").forEach((el) => el.remove());
    this.#createHeader();
    if (this.tableView === "row") {
      await this.#refreshTableRow();
    } else if (this.tableView === "cards") {
      await this.#refreshTableCards();
    }
    this.#triggerCallback("onAfterTableCreate");
    const newItems = this.nodeTable.querySelectorAll("[data-translate]");
    await translate(newItems);
  };

  #refreshTableCards = async () => {
    const table = this.nodeTable;
    table.classList.add("table-display-card");
    table.classList.remove("table-display-grid");
    const cells = table.querySelectorAll(".container-card");
    for (let element of cells) {
      element.remove();
    }
    const containerCard = document.createElement("div");
    containerCard.classList.add("container-card");
    table.append(containerCard);
    let query = "";
    if (this.searchPanel) {
      query = this.searchPanel.value.toLowerCase().trim();
    }
    await this.#refreshFilterData();
    let lzImg = false;
    for (let row in this.inPutData) {
      if (this.inPutData[row]["changed"] === 2) continue;
      if (!this.#isRowValid(row, query)) continue;
      const card = document.createElement("div");
      card.classList.add("card-table-cell");
      containerCard.append(card);
      const theme = row % 2 === 0 ? "table-cell-dark" : "table-card-cell-light";
      for (let column in this.tableFields) {
        if (this.tableFields[column].type === "img") {
          lzImg = true;
          const img = document.createElement("img");
          img.dataset.srcset = this.#getValue(row, column);
          img.alt = "";
          card.append(img);
        } else if (this.tableFields[column].type === "button") {
          const div = document.createElement("div");
          div.classList.add("table-cell", "table-cell-padding");
          div.dataset.column = column;
          div.classList.add(theme);
          if (this.tableFields[column].menu !== undefined) {
            const button = document.createElement("button");
            button.classList.add("table-btn");
            const menu = { ...this.tableFields[column].menu };
            Object.keys(menu).forEach((k) => {
              if (typeof menu[k] === "object" && menu[k] !== null) {
                menu[k] = { ...menu[k], row: row };
              }
            });
            button.__menuStorage = {
              context: this,
              menu: menu,
              settings: { type: "button", parent: null, children: [], isParentNav: false },
            };
            button.textContent = "▼";
            div.append(button);
          }
          card.append(div);
        } else if (this.tableFields[column].type === "table") {
          const div0 = document.createElement("div");
          div0.classList.add("table-card-cell-header-dark");
          div0.dataset.translate = this.tableFields[column].columnName;
          card.append(div0);
          const div = document.createElement("div");
          div.classList.add("table-cell", "table-cell-padding");
          div.classList.add(theme);
          div.dataset.column = column;
          const div3 = document.createElement("div");
          div3.classList.add("table-cell-flex-direction-row-space-between");
          div.append(div3);
          const div2 = document.createElement("div");
          const value = this.#getValue(row, column);
          div2.dataset.translate = formatDataValue(value, this.tableFields[column].template, this.tableFields[column].nameFieldDatabase);
          div2.classList.add("table-cell-pre-line");
          div3.append(div2);
          const button = document.createElement("button");
          button.classList.add("table-btn");
          button.textContent = "+";
          const menu = { ...this.tableFields[column].menu };
          Object.keys(menu).forEach((k) => {
            if (typeof menu[k] === "object" && menu[k] !== null) {
              menu[k] = { ...menu[k], row: row };
            }
          });
          button.__menuStorage = {
            context: this,
            menu: menu,
            settings: { type: "button", parent: null, children: [], isParentNav: false, node: button },
          };
          div3.append(button);
          card.append(div);
        } else if (this.tableFields[column].type === "text") {
          const div = document.createElement("div");
          div.classList.add("table-card-cell-header-dark");
          div.dataset.translate = this.tableFields[column].columnName;
          card.append(div);
          const div1 = document.createElement("div");
          div1.classList.add("table-cell", "table-cell-pre-line");
          div1.classList.add(theme);
          const value = formatDataValue(this.#getValue(row, column), this.tableFields[column].template, this.tableFields[column].nameFieldDatabase);
          if (this.tableFields[column].notTranslate) {
            div1.textContent = value;
          } else {
            div1.dataset.translate = value;
          }
          this.callbackCellRender?.({ cell: div1, inPutData: this.inPutData, row, tableFields: this.tableFields[column] });
          card.append(div1);
        }
      }
    }
    if (lzImg === true) {
      const lazyImages = document.querySelectorAll("[data-srcset]");
      lazyImagesStart(lazyImages);
    }
  };

  #refreshFilterData = async () => {
    for (let row in this.inPutData) {
      this.inPutData[row]["filterData"] = "";
      let elements = [];
      for (let column in this.tableFields) {
        if (["table", "a", "text"].includes(this.tableFields[column].type)) {
          const value = this.#getValue(row, column);
          const valueFormat = formatDataValue(value, this.tableFields[column].template, this.tableFields[column].nameFieldDatabase, false);
          const cleanColumnValues = valueFormat
            .map((item) =>
              String(item)
                .replace(/<\/?[^>]+(>|$)/g, "")
                .replace(/\\n|\n/g, " "),
            )
            .filter((item) => item !== "");
          if (this.tableFields[column].notTranslate) {
            cleanColumnValues[0] = "*" + cleanColumnValues[0];
          }
          elements.push(...cleanColumnValues, " ");
        }
      }
      const mockElement = {
        tagName: "DIV",
        dataset: { translate: JSON.stringify(elements).replace(/"/g, "'") },
        textContent: "",
        querySelector() {
          return null;
        },
        set innerHTML(val) {
          this.textContent = val;
        },
      };
      await translate([mockElement]);
      this.inPutData[row]["filterData"] = mockElement.textContent.toLowerCase();
    }
  };

  #refreshTableRow = async () => {
    const table = this.nodeTable;
    table.classList.remove("table-display-card");
    table.classList.add("table-display-grid");
    const cells = table.querySelectorAll(".table-cell:not(.table-header-cell)");
    for (let element of cells) {
      element.remove();
    }
    let lzImg = false;
    let numberRow = 1;
    let query = "";
    if (this.searchPanel) {
      query = this.searchPanel.value.toLowerCase().trim();
    }
    await this.#refreshFilterData();
    for (let row in this.inPutData) {
      if (this.inPutData[row]["changed"] === 2) continue;
      if (!this.#isRowValid(row, query)) continue;
      const theme = row % 2 === 0 ? "table-cell-dark" : "table-cell-light";
      for (let column in this.tableFields) {
        if (this.tableFields[column].type === "line number") {
          const div = document.createElement("div");
          div.classList.add("table-cell");
          div.classList.add(theme);
          div.dataset.column = column;
          div.textContent = numberRow;
          table.append(div);
        } else if (this.tableFields[column].type === "img") {
          lzImg = true;
          const div = document.createElement("div");
          div.classList.add("table-cell", "table-cell-image");
          div.dataset.column = column;
          const img = document.createElement("img");
          img.dataset.column = column;
          img.dataset.srcset = this.#getValue(row, column);
          img.alt = "";
          div.append(img);
          table.append(div);
        } else if (this.tableFields[column].type === "button") {
          const div = document.createElement("div");
          div.classList.add("table-cell", "table-cell-padding");
          div.dataset.column = column;
          div.classList.add(theme);
          if (this.tableFields[column].menu !== undefined) {
            const button = document.createElement("button");
            button.classList.add("table-btn");
            const menu = { ...this.tableFields[column].menu };
            Object.keys(menu).forEach((k) => {
              if (typeof menu[k] === "object" && menu[k] !== null) {
                menu[k] = { ...menu[k], row: row };
              }
            });
            button.__menuStorage = {
              context: this,
              menu: menu,
              settings: { type: "button", parent: null, children: [], isParentNav: false },
            };
            button.textContent = "▼";
            div.append(button);
          }
          table.append(div);
        } else if (this.tableFields[column].type === "table") {
          const div = document.createElement("div");
          div.classList.add("table-cell", "table-cell-padding");
          div.classList.add(theme);
          div.dataset.column = column;
          const div3 = document.createElement("div");
          div3.classList.add("table-cell-flex-direction-row-space-between");
          div.append(div3);
          const div2 = document.createElement("div");
          const value = this.#getValue(row, column);
          div2.dataset.translate = formatDataValue(value, this.tableFields[column].template, this.tableFields[column].nameFieldDatabase);
          div2.classList.add("table-cell-pre-line");
          div3.append(div2);
          const button = document.createElement("button");
          button.classList.add("table-btn");
          button.textContent = "+";
          const menu = { ...this.tableFields[column].menu };
          Object.keys(menu).forEach((k) => {
            if (typeof menu[k] === "object" && menu[k] !== null) {
              menu[k] = { ...menu[k], row: row };
            }
          });
          button.__menuStorage = {
            context: this,
            menu: menu,
            settings: { type: "button", parent: null, children: [], isParentNav: false, node: button },
          };
          div3.append(button);
          table.append(div);
        } else if (this.tableFields[column].type === "a") {
          const div = document.createElement("div");
          div.classList.add("table-cell", "table-cursor-pointer");
          div.classList.add(theme);
          div.dataset.column = column;
          const a = document.createElement("a");
          const value = this.#getValue(row, column);
          a.dataset.translate = formatDataValue(value, this.tableFields[column].template, this.tableFields[column].nameFieldDatabase);
          a.href = this.inPutData[row][this.tableFields[column].link];
          a.target = "_blank";
          if (this.tableFields[column].preLine) {
            a.classList.add("table-cell-pre-line");
          }
          div.append(a);
          table.append(div);
        } else if (this.tableFields[column].type === "text") {
          const div = document.createElement("div");
          const value = formatDataValue(this.#getValue(row, column), this.tableFields[column].template, this.tableFields[column].nameFieldDatabase);
          if (this.tableFields[column].notTranslate) {
            div.textContent = value;
          } else {
            div.dataset.translate = value;
          }
          div.classList.add("table-cell");
          if (this.tableFields[column].preLine) {
            div.classList.add("table-cell-pre-line");
          }
          div.classList.add(theme);
          if (this.tableFields[column].textPosition === "start") {
            div.classList.add("table-cell-text-align-start");
          }
          div.dataset.column = column;
          if (this.tableFields[column]?.menu?.[0]?.flatExecution) {
            const menu = { ...this.tableFields[column].menu };
            Object.keys(menu).forEach((k) => {
              if (typeof menu[k] === "object" && menu[k] !== null) {
                menu[k] = { ...menu[k], row: row };
              }
            });
            div.__menuStorage = {
              context: this,
              menu,
              settings: { type: "button", parent: null, children: [], isParentNav: false, node: div },
            };
          }
          this.callbackCellRender?.({ cell: div, inPutData: this.inPutData, row, tableFields: this.tableFields[column] });
          table.append(div);
        }
      }
      ++numberRow;
    }
    table.style.gridTemplateColumns = this.#setGridColumns();
    if (lzImg === true) {
      const lazyImages = document.querySelectorAll("[data-srcset]");
      lazyImagesStart(lazyImages);
    }
  };

  #setGridColumns = () => {
    const gridCols = Object.values(this.tableFields)
      .filter((col) => col.type && col.type !== "skip")
      .map((col) => {
        const w = String(col.width || "");
        return w.includes("px") ? w : "minmax(max-content, 1fr)";
      })
      .join(" ");
    return gridCols;
  };

  actionClick = async (event) => {
    const menuStorage = event.target.__menuStorage ? event.target.__menuStorage : event.target.parentElement.__menuStorage;
    if (!menuStorage || menuStorage.context !== this) return;
    if (menuStorage.menu.action === "add") {
      if (menuStorage.menu.onBeforeModalOpen === true) {
        if (!(await this.callBackAction(menuStorage))) return;
      }
      const mW = new modalWindow();
      mW.fields = this.tableFields;
      mW.parent = this;
      //mW.inPutData = this.inPutData;
      mW.inPutData = this.additionalInfo; //.inPutData;
      mW.row = this.additionalInfo.row;
      mW.buttons = ["global.button.yes", "global.button.no"];
      mW.header = "modalWindow.inscription.changeData";
      mW.action = menuStorage.menu.action;
      mW.setSettings();
      if (!(await mW.show())) return;
      if (menuStorage.menu.onAfterModalOpen === true) {
        menuStorage.settings["outPutData"] = mW.outPutData;
        if (!(await this.callBackAction(menuStorage))) return;
      }
      this.#add(menuStorage, mW.outPutData);
    } else if (menuStorage.menu.action === "save") {
      if (menuStorage.menu.onBeforeModalOpen === true) {
        if (!(await this.callBackAction(menuStorage))) return;
      }
      const mW = new modalWindow();
      mW.inPutData = { 0: { message: JSON.stringify([menuStorage.menu.confirmKey, "?"]) } };
      mW.row = 0;
      mW.fields = {
        0: {
          type: "p",
          nameFieldDatabase: ["message"],
          editorFieldNames: { message: "" },
          editorType: { message: "p" },
        },
      };
      mW.parent = this;
      mW.buttons = ["global.button.yes", "global.button.no"];
      mW.header = "modalWindow.inscription.question";
      mW.setSettings();
      if (!(await mW.show())) return;
      if (menuStorage.menu.onAfterModalOpen === true) {
        if (!(await this.callBackAction(menuStorage))) return;
      }
      this.#save(menuStorage);
    } else if (menuStorage.menu.action === "edit") {
      if (menuStorage.menu.onBeforeModalOpen === true) {
        if (!(await this.callBackAction(menuStorage))) return;
      }
      const mW = new modalWindow();
      mW.fields = this.tableFields;
      mW.parent = this;
      mW.inPutData = this.inPutData;
      mW.row = menuStorage.menu.row;
      mW.buttons = ["global.button.yes", "global.button.no"];
      mW.header = "modalWindow.inscription.changeData";
      mW.action = menuStorage.menu.action;
      mW.setSettings();
      if (!(await mW.show())) return;
      if (menuStorage.menu.onAfterModalOpen === true) {
        menuStorage.settings["outPutData"] = mW.outPutData;
        if (!(await this.callBackAction(menuStorage))) return;
      }
      this.#edit(menuStorage, mW.outPutData);
    } else if (menuStorage.menu.action === "showFoto") {
      const mW = new modalWindow();
      mW.fields = this.tableFields;
      mW.parent = this;
      mW.inPutData = this.inPutData;
      mW.row = menuStorage.menu.row;
      //mW.buttons = ["global.button.left", "global.button.right", "global.button.close"];
      mW.buttons = ["◀", "▶", "global.button.close"];
      mW.header = "";
      mW.action = menuStorage.menu.action;
      mW.extraParameter = menuStorage.menu.extraParameter;
      mW.setSettings();
      await mW.showFoto();
    } else if (menuStorage.menu.action === "del") {
      if (menuStorage.menu.onAfterModalOpen === true) {
        if (!(await this.callBackAction(menuStorage))) return;
      }
      this.#del(menuStorage);
    } else if (menuStorage.menu.action === "up") {
      this.#up(menuStorage.menu.row);
    } else if (menuStorage.menu.action === "down") {
      this.#down(menuStorage.menu.row);
    } else if (menuStorage.menu.action === "pinned") {
      this.btnIsPinned = !this.btnIsPinned;
      api.setLocalStorage(localStoragePath + ":" + this.objectId + ":filters:isPinned", this.btnIsPinned);
      this.#isPinned(event.target);
    } else if (menuStorage.menu.action === "viewingMode") {
      this.tableView = menuStorage.menu.value;
      api.setLocalStorage(localStoragePath + ":" + this.objectId + ":filters:tableView", menuStorage.menu.value);
      this.#changeViewTable();
    } else if (menuStorage.menu.action === "resetTextFilter") {
      this.searchPanel.value = "";
      this.refreshTable();
    } else if (menuStorage.menu.action === "setTextFilter") {
      this.refreshTable();
    } else if (menuStorage.menu.action === "toggleCheckbox") {
      if (event.target.type === "checkbox") {
        const menustorageKey = `${localStoragePath}:${menuStorage.menu.storage}`;
        const checkbox = menuStorage.menu.checkboxNode;
        const checked = checkbox?.checked;
        api.setLocalStorage(menustorageKey, checked);
      } else {
        this.loadDataAndRefresh();
      }
    } else if (menuStorage.menu.action === "numberOfRow") {
      api.setLocalStorage(localStoragePath + ":" + this.objectId + ":filters:numberOfRow", menuStorage.menu.value);
      this.buttonNumerLines.textContent = menuStorage.menu.label;
      this.refresh = true;
      this.loadData();
    } else if (menuStorage.menu.action === "newTableOpen") {
      this.callBackAction(menuStorage);
    } else {
      this.callBackAction(menuStorage);
    }
  };

  #up = (row) => {
    if (+row === 0) return;
    const keys = Object.keys(this.inPutData).map(Number);
    const currentIndex = keys.indexOf(+row);
    if (currentIndex <= 0) return;
    const previousRow = keys[currentIndex - 1];
    [this.inPutData[row], this.inPutData[previousRow]] = [this.inPutData[previousRow], this.inPutData[row]];
    let counter = 0;
    for (let key in this.inPutData) {
      this.inPutData[key].ordering = counter++;
    }
    this.isChange = true;
    this.updateStatus();
    this.refreshTable();
  };

  #down = (row) => {
    row = +row;
    let nextIndex = -1;
    let stop = false;
    for (let value in this.inPutData) {
      if (row === +value) {
        stop = true;
      } else if (stop === true) {
        nextIndex = +value;
        break;
      }
    }
    if (nextIndex === -1) return;
    [this.inPutData[row], this.inPutData[nextIndex]] = [this.inPutData[nextIndex], this.inPutData[row]];
    // Reassign all ordering values to ensure correct INT sequence
    let counter = 0;
    for (let key in this.inPutData) {
      this.inPutData[key].ordering = counter++;
    }
    this.isChange = true;
    this.updateStatus();
    this.refreshTable();
  };

  #add = (menuStorage, outPutData) => {
    const dataAdd = {
      ...outPutData,
      ...this.additionalInfo,
    };
    this.inPutData.push(dataAdd);
    if (menuStorage.menu?.onBeforeAdd === true) {
      this.callBackAction(menuStorage);
    }
    this.isChange = true;
    this.updateStatus();
    this.refreshTable();
  };

  #edit = (menuStorage, outPutData) => {
    const row = menuStorage.menu.row;
    let hasChanges = false;
    Object.keys(outPutData).forEach((key) => {
      if (key in this.inPutData[row]) {
        if (this.inPutData[row][key] !== outPutData[key]) {
          this.inPutData[row][key] = outPutData[key];
          hasChanges = true;
        }
      }
    });
    if (menuStorage.menu?.onBeforeEdit === true) {
      this.callBackAction(menuStorage);
    }
    if (hasChanges === true) {
      this.isChange = true;
      this.updateStatus();
    }
    this.refreshTable();
  };

  #del = (menuStorage) => {
    this.inPutData[menuStorage.menu.row]["changed"] = 2;
    if (menuStorage.menu?.onBeforeDel === true) {
      this.callBackAction(menuStorage);
    }
    this.isChange = true;
    this.updateStatus();
    this.refreshTable();
  };

  updateStatus = () => {
    if (!this.saveButtonMenu) return;
    const badge = this.saveButtonMenu.querySelector(".table-unsaved-badge");
    if (badge) {
      badge.style.display = this.isChange ? "block" : "none";
    }
  };

  #save = async (menuStorage) => {
    document.querySelector("#indicatorBook").classList.remove("indicator-book-none");
    const queryData = this.#getSaveData();
    if (!queryData || queryData.length === 0) {
      document.querySelector("#indicatorBook").classList.add("indicator-book-none");
      this.isChange = false;
      this.updateStatus();
      reportStatus(["❗", "modalWindow.inscription.noDataToSave"]);
      return;
    }
    const [phpFile, action] = this.callPhpSave;
    if (menuStorage.menu?.onBeforeSave === true) {
      menuStorage["settings"]["queryData"] = queryData;
      await this.callBackAction(menuStorage);
    }
    let dt;
    if (this.formData) {
      dt = new FormData();
      dt.append("table", this.queryParams.tables[0]);
      dt.append("action", action);
      queryData.forEach((item, index) => {
        if (item.blobFile) {
          const fileName = item.dataSrcset.split("/").pop();
          dt.append(`file_${index}`, item.blobFile, fileName);
        }
        delete item.blobFile;
      });
      dt.append("queryData", JSON.stringify(queryData));
    } else {
      dt = {
        table: this.queryParams.tables[0],
        action: action,
        queryData: queryData,
      };
    }
    const loadingPromises = [api.send(dt, phpFile)];
    const [result] = await api.safeLoad(loadingPromises);
    await reportStatus(result);
    document.querySelector("#indicatorBook").classList.add("indicator-book-none");
    if (menuStorage.menu?.onAfterSave === true) {
      menuStorage.menu.action = "onAfterSave";
      this.callBackAction(menuStorage);
    }
    this.isChange = false;
    this.updateStatus();
  };

  #getSaveData = () => {
    /*this.savingSettings = {
      ignoredKeys: { edit: ["key3"], add: ["key1","key2"] ,del:["key4"]},
      forceSendKeys:{ edit: ["key5"], add: ["key6","key7"] ,del:["key8"]},
    };*/
    const changedData = [];
    if (!this.inPutData || this.inPutData.length === 0) {
      return changedData;
    }
    this.inPutData.forEach((currentRow, index) => {
      const originalRow = this.inPutDataOrigenal[index];
      //if (!("id" in currentRow)) {
      const isNewRow = !("id" in currentRow) || currentRow["id"] === "" || currentRow["id"] === null || currentRow["id"] === undefined;
      if (isNewRow) {
        const ignoredKeysAdd = this.savingSettings?.ignoredKeys?.add ?? [];
        const newRow = { ...currentRow, changed: 1 };
        ignoredKeysAdd.forEach((key) => {
          delete newRow[key];
        });
        changedData.push(newRow);
        return;
      }
      if (originalRow && currentRow) {
        if (currentRow["changed"] === 2) {
          const forceSendKeysDel = this.savingSettings?.forceSendKeys?.del ?? [];
          const deleteRow = { changed: 2 };
          //if ("id" in currentRow) {
          if ("id" in currentRow && currentRow["id"] !== "") {
            deleteRow["id"] = currentRow["id"];
          }
          forceSendKeysDel.forEach((key) => {
            if (key in currentRow) {
              deleteRow[key] = currentRow[key];
            }
          });
          changedData.push(deleteRow);
          return;
        }
        const ignoredKeysEdit = this.savingSettings?.ignoredKeys?.edit ?? [];
        const forceSendKeysEdit = this.savingSettings?.forceSendKeys?.edit ?? [];
        const rowChanges = {};
        Object.keys(currentRow).forEach((key) => {
          if (!ignoredKeysEdit.includes(key) && currentRow[key] !== originalRow[key]) {
            rowChanges[key] = currentRow[key];
          }
        });
        if (Object.keys(rowChanges).length > 0) {
          //if ("id" in currentRow) {
          if ("id" in currentRow && currentRow["id"] !== "") {
            rowChanges["id"] = currentRow["id"];
          }
          rowChanges["changed"] = 3;
          forceSendKeysEdit.forEach((key) => {
            if (key in currentRow) {
              rowChanges[key] = currentRow[key];
            }
          });
          changedData.push(rowChanges);
        }
      }
    });
    return changedData;
  };

  #getValue = (row, column) => {
    const { enumeration, nameFieldDatabase } = this.tableFields[column];
    const result = [];
    for (const key of nameFieldDatabase) {
      if (this.tableFields[column].notVisibleInTable && this.tableFields[column].notVisibleInTable.includes(key)) continue;
      const dt = this.inPutData[row]?.[key];
      const enumItem = enumeration?.[key]?.[dt];
      const finalValue = enumItem?.value ?? enumItem ?? dt;
      result.push(finalValue);
    }
    return result;
  };

  #isRowValid = (row, query) => {
    if (!query) return true;
    const rowText = this.inPutData?.[row]["filterData"];
    if (!rowText) return true;
    return rowText.includes(query);
  };

  /* #applyHighlighting = () => {
    if (!this.searchPanel) return;
    const query = this.searchPanel.value.toLowerCase().trim();
    const selector = this.tableView === "cards" ? ".card-table-cell div" : ".table-cell:not(.table-header-cell)";
    const allCells = this.nodeTable.querySelectorAll(selector);
    const ranges = [];
    allCells.forEach((cell) => {
      const textNode = cell.firstChild;
      if (!textNode || textNode.nodeType !== 3) return;
      const text = textNode.textContent.toLowerCase();
      let startPos = 0;
      if (query && text.includes(query)) {
        while ((startPos = text.indexOf(query, startPos)) !== -1) {
          const range = new Range();
          range.setStart(textNode, startPos);
          range.setEnd(textNode, startPos + query.length);
          ranges.push(range);
          startPos += query.length;
        }
      }
    });
    if (ranges.length > 0 && CSS.highlights) {
      CSS.highlights.set("search-results", new Highlight(...ranges));
    }
  };*/

  #applyHighlighting = () => {
    if (!this.searchPanel) return;
    const query = this.searchPanel.value.toLowerCase().trim();
    const selector = this.tableView === "cards" ? ".card-table-cell div" : ".table-cell:not(.table-header-cell)";
    const allCells = this.nodeTable.querySelectorAll(selector);
    const ranges = [];
    allCells.forEach((cell) => {
      const walker = document.createTreeWalker(cell, NodeFilter.SHOW_TEXT, null, false);
      let textNode;
      while ((textNode = walker.nextNode())) {
        const text = textNode.textContent.toLowerCase();
        let startPos = 0;
        if (query && text.includes(query)) {
          while ((startPos = text.indexOf(query, startPos)) !== -1) {
            const range = new Range();
            range.setStart(textNode, startPos);
            range.setEnd(textNode, startPos + query.length);
            ranges.push(range);
            startPos += query.length;
          }
        }
      }
    });
    if (CSS.highlights) {
      if (ranges.length > 0) {
        CSS.highlights.set("search-results", new Highlight(...ranges));
      } else {
        CSS.highlights.delete("search-results");
      }
    }
  };

  #isPinned = (btn) => {
    btn.classList.toggle("table-btn-is-pinned", this.btnIsPinned);
    this.nodeTable.classList.toggle("table-display-grid-is-pinned", this.btnIsPinned);
    if (this.btnIsPinned) {
      const pageContainer = this.nodeTable.closest(".page");
      if (this.nodeTable.parentElement !== pageContainer) {
        pageContainer.appendChild(this.nodeTable);
      }
      const currentScroll = pageContainer.scrollTop;
      this.nodeTable.style.top = `${currentScroll + this.topOffset}px`;
    } else {
      this.parentCell?.appendChild(this.nodeTable);
    }
  };
}
