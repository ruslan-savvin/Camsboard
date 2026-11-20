//Savvin Ruslan 2024
/**
 * Sample of the database read function call
 * @example
 * // 1. Prepare the request object
 * const dataRequest = {
 *   queryParams: {
 *     select: { 0: { action: "+", prefix: "main_table_name", columns: "column_name_1" } },
 *     tables: ["main_table_name"],
 *     join: "",
 *     where: `WHERE main_table_name.column_name_1 IN (${values})`,
 *     //This option is for substituting filter data from local storage
 *     where: `WHERE imageResizes.resize {DATA_HERE/imageResizes.resize} AND images.deviceId {DATA_HERE/images.deviceId}`;
 *     groupBy: "GROUP BY main_table_name.column_name_1",
 *     orderBy: "ORDER BY main_table_name.column_name_2",
 *     limit: "LIMIT 10",
 *     buildMap: [["column_name_1"], ["column_name_2", "column_name_3"]]
 *   }
 * };
 * // buildMap: Reconstructs the output object by grouping fields.
 * // The first array defines the grouping keys, the second defines the nested data structure.
 */

/**
 * Sample of the database write function call (Update/Insert)
 * @example
 * // 1. Prepare data and conditions
 * const actionData = { insert: { column_name_2: value } };
 * const condition = {
 *   changed: 5,
 *   where: `WHERE main_table.column_name_1 IN (${values})`
 * };
 *
 * const data = {
 *   table: "main_table",
 *   queryData: {
 *     0: { ...actionData, ...condition }
 *   }
 * };
 */

import { reportStatus } from "/js/common/GeneralFunctions.js";
import { api } from "/js/common/ApiClient.js";
export class DynamicForm {
  constructor() {
    this.mountPoint = "";
    this.localizationMap = null;
    this.callPhpLoader = [];
    this.callPhpSave = [];
    this.inPutData = null;
    this.additionalInfo = null;
    this.checkboxFields = [];
    this.myInsertions = {};
    this.saveAttribute = "";
    this.queryParams = null;
    this.textarea = null;
    Object.preventExtensions(this);
  }
  async load() {
    const [phpFile, action] = this.callPhpLoader;
    const data = {
      action: action,
      queryParams: this.queryParams,
    };
    const result = await api.send(data, phpFile);
    this.inPutData = result.data;
    this.additionalInfo = {
      ...this.additionalInfo,
      id: this.inPutData[0]?.id,
    };
    return true;
  }
  render() {
    const container = document.getElementById(this.mountPoint);
    const item = this.inPutData[0];
    container.innerHTML = "";
    Object.keys(this.localizationMap).forEach((key) => {
      const dbKey = key.startsWith("#") ? key.substring(1) : key;
      if (item[dbKey] === undefined) return;
      if (this.myInsertions[dbKey]) {
        container.insertAdjacentHTML("beforeend", this.myInsertions[dbKey]);
      }
      const translationCode = this.localizationMap[key];
      let input = null;
      const val = item[dbKey];
      if (this.textarea.includes(dbKey)) {
        input = document.createElement("textarea");
        input.addEventListener("input", () => this.autoResize(input));
      } else {
        input = document.createElement("input");
      }
      input.id = dbKey;
      if (this.checkboxFields.includes(dbKey)) {
        const legend = document.createElement("legend");
        legend.className = "legend";
        const label = document.createElement("label");
        label.className = "checkbox-ios";
        input.type = "checkbox";
        input.checked = val == 1;
        const span = document.createElement("span");
        span.className = "checkbox-ios-switch";
        const p = document.createElement("p");
        p.setAttribute("data-translate", translationCode);
        label.appendChild(input);
        label.appendChild(span);
        legend.appendChild(label);
        legend.appendChild(p);
        container.appendChild(legend);
      } else {
        const fieldset = document.createElement("fieldset");
        fieldset.className = "fieldset fieldset-shadow";
        const legend = document.createElement("legend");
        legend.className = "legend";
        legend.setAttribute("data-translate", translationCode);
        input.className = "input-fieldset";
        input.value = val || "";
        fieldset.appendChild(legend);
        fieldset.appendChild(input);
        container.appendChild(fieldset);
        if (this.textarea.includes(dbKey)) {
          this.autoResize(input);
        } else {
          input.type = "text";
        }
      }
      input.onchange = (e) => {
        const currentVal = e.target.type === "checkbox" ? (e.target.checked ? 1 : 0) : e.target.value;
        if (currentVal != item[dbKey]) {
          e.target.setAttribute(this.saveAttribute, item.id);
        } else {
          e.target.removeAttribute(this.saveAttribute);
        }
      };
    });
  }

  autoResize(input) {
    input.style.height = "auto";
    input.style.height = input.scrollHeight + "px";
  }

  async save() {
    const container = document.getElementById(this.mountPoint);
    const changedInputs = container.querySelectorAll(`[${this.saveAttribute}]`);
    const dataTemp = {};
    changedInputs.forEach((input) => {
      dataTemp[input.id] = input.type === "checkbox" ? (input.checked ? 1 : 0) : input.value;
    });
    const [phpFile, action] = this.callPhpSave;
    const data = {
      table: this.queryParams.tables[0],
      action: action,
      queryData: Object.keys(dataTemp).length ? { 0: { ...dataTemp, ...this.additionalInfo } } : { 0: { ...dataTemp } },
    };
    const loadingPromises = [api.send(data, phpFile)];
    const [result] = await api.safeLoad(loadingPromises);
    changedInputs.forEach((input) => input.removeAttribute(this.saveAttribute));
    Object.assign(this.inPutData[0], dataTemp);
    reportStatus(result);
  }
}
