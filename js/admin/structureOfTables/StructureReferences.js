//Savvin Ruslan 2025
import { CommonData } from "/js/admin/CommonData.js";

export class StructureReferences {
  constructor(context) {
    this.context = context;
  }

  init = (version) => {
    const methodName = `init${version}`;
    return this[methodName]();
  };
  init1 = () => {
    /**
     * Description of the structure of the device table and its menu
     */
    const menu = {};
    const mergedMenu = {
      ...CommonData.classicMenu,
      ...menu,
    };
    const tableFields = {
      0: {
        type: "button",
        width: "150px",
        menu: mergedMenu,
        menuHeader: CommonData.classicMenuHeader,
      },
      1: {
        type: "line number",
        width: "auto",
        columnName: "№",
      },
      2: {
        type: "text",
        width: "auto",
        columnName: "table.devices.col.deviceName",
        nameFieldDatabase: ["deviceName"],
        editorFieldNames: { deviceName: "table.devices.col.deviceName" },
        editorType: { deviceName: "input text" },
      },
      3: {
        type: "text",
        width: "auto",
        columnName: "table.devices.col.isDelete",
        nameFieldDatabase: ["isDelete"],
        enumeration: { isDelete: CommonData.enumYesNo },
        editorFieldNames: { isDelete: "table.devices.col.isDelete" },
        editorType: { isDelete: "select" },
      },
      4: {
        type: "text",
        width: "auto",
        columnName: "table.devices.col.isDefault",
        nameFieldDatabase: ["isDefault"],
        enumeration: { isDefault: CommonData.enumYesNo },
        editorFieldNames: { isDefault: "table.devices.col.isDefault" },
        editorType: { isDefault: "select" },
      },
      5: {
        type: "text",
        width: "auto",
        columnName: "Stream key",
        nameFieldDatabase: ["streamKey"],
        editorFieldNames: { streamKey: "Stream key" },
        editorType: { streamKey: "input text" },
      },
      6: {
        type: "text",
        width: "auto",
        columnName: "Width",
        nameFieldDatabase: ["width"],
        editorFieldNames: { width: "Width" },
        editorType: { width: "input text" },
      },
      7: {
        type: "text",
        width: "auto",
        columnName: "Height",
        nameFieldDatabase: ["height"],
        editorFieldNames: { height: "Height" },
        editorType: { height: "input text" },
      },

      8: {
        type: "text",
        width: "auto",
        columnName: "Address",
        nameFieldDatabase: ["address"],
        editorFieldNames: { address: "Address" },
        editorType: { address: "input text" },
      },
      9: {
        type: "text",
        width: "auto",
        columnName: "Only кey frames",
        nameFieldDatabase: ["onlyKeyFrames"],
        enumeration: { onlyKeyFrames: CommonData.enumYesNo },
        editorFieldNames: { onlyKeyFrames: "Only кey frames" },
        editorType: { onlyKeyFrames: "select" },
      },
      10: {
        type: "text",
        width: "auto",
        columnName: "Scene threshold",
        nameFieldDatabase: ["sceneThreshold"],
        editorFieldNames: { sceneThreshold: "Scene threshold" },
        editorType: { sceneThreshold: "input number" },
      },
      11: {
        type: "text",
        width: "auto",
        columnName: "FPS",
        nameFieldDatabase: ["fps"],
        editorFieldNames: { fps: "FPS" },
        editorType: { fps: "input number" },
      },
    };
    return tableFields;
  };

  init2 = () => {
    /**
     * Description of the structure of the languages table and its menu
     */
    const menu = {
      2: {
        action: "up",
        label: "global.menu.up",
      },
      3: {
        action: "down",
        label: "global.menu.down",
      },
    };
    const mergedMenu = {
      ...CommonData.classicMenu,
      ...menu,
    };
    const tableFields = {
      0: {
        type: "button",
        width: "150px",
        menu: mergedMenu,
        menuHeader: CommonData.classicMenuHeader,
      },
      1: {
        type: "line number",
        width: "auto",
        columnName: "№",
      },
      2: {
        type: "text",
        width: "auto",
        columnName: "table.languages.col.delete",
        enumeration: { isDelete: CommonData.enumYesNo },
        nameFieldDatabase: ["isDelete"],
        editorFieldNames: { isDelete: "table.languages.editor.delete" },
        editorType: { isDelete: "select" },
      },
      3: {
        type: "text",
        width: "auto",
        columnName: "table.languages.col.languageCode",
        nameFieldDatabase: ["languageCode"],
        editorFieldNames: { languageCode: "table.languages.editor.languageCode" },
        editorType: { languageCode: "input text" },
      },
      4: {
        type: "text",
        width: "auto",
        columnName: "table.languages.col.languageName",
        nameFieldDatabase: ["languageName"],
        editorFieldNames: { languageName: "table.languages.editor.languageName" },
        editorType: { languageName: "input text" },
      },
      5: {
        type: "text",
        width: "auto",
        columnName: "table.languages.col.base",
        enumeration: { base: CommonData.enumYesNo },
        nameFieldDatabase: ["base"],
        editorFieldNames: { base: "table.languages.editor.base" },
        editorType: { base: "select" },
      },
      6: {
        type: "text",
        width: "auto",
        columnName: "table.languages.col.flag",
        nameFieldDatabase: ["flag"],
        editorFieldNames: { flag: "table.languages.editor.flag" },
        editorType: { flag: "input text" },
      },
      7: {
        type: "text",
        width: "auto",
        columnName: "table.languages.col.ordering",
        nameFieldDatabase: ["ordering"],
      },
      8: {
        type: "text",
        width: "auto",
        columnName: "table.languages.col.adminPanel",
        enumeration: { adminPanel: CommonData.enumYesNo },
        nameFieldDatabase: ["adminPanel"],
        editorFieldNames: { adminPanel: "table.languages.editor.adminPanel" },
        editorType: { adminPanel: "select" },
      },
    };
    return tableFields;
  };

  init4 = () => {
    /**
     * Description of the structure of the users table and its menu
     */

    const tableFields = {
      0: {
        type: "button",
        width: "150px",
        menu: CommonData.classicMenu,
        menuHeader: CommonData.classicMenuHeader,
      },
      1: {
        type: "line number",
        width: "auto",
        columnName: "№",
      },
      2: {
        type: "text",
        width: "auto",
        columnName: "table.users.col.delete",
        enumeration: { isDelete: CommonData.enumYesNo },
        nameFieldDatabase: ["isDelete"],
        editorFieldNames: { isDelete: "table.users.editor.delete" },
        editorType: { isDelete: "select" },
      },
      3: {
        type: "text",
        width: "auto",
        columnName: "table.users.col.token",
        nameFieldDatabase: ["token"],
        editorFieldNames: { token: "table.users.editor.token" },
        editorType: { token: "input text" },
      },
      4: {
        type: "text",
        width: "auto",
        columnName: "table.users.col.name",
        nameFieldDatabase: ["name", "surname"],
        editorFieldNames: { name: "table.users.editor.name", surname: "table.users.editor.surname" },
        editorType: { name: "input text", surname: "input text" },
        template: {
          name: { separators: ["{DATA_HERE}", "\n"] },
          surname: { separators: ["{DATA_HERE}", "\n"] },
        },
        preLine: true,
      },
      5: {
        type: "text",
        width: "auto",
        columnName: "table.users.col.role",
        enumeration: { role: CommonData.enumRole },
        nameFieldDatabase: ["role"],
        editorFieldNames: { role: "table.users.editor.role" },
        editorType: { role: "select" },
      },
      6: {
        type: "text",
        width: "auto",
        columnName: "Telegram",
        nameFieldDatabase: ["telegram"],
        editorFieldNames: { telegram: "Telegram" },
        editorType: { telegram: "input text" },
      },
      7: {
        type: "text",
        width: "auto",
        columnName: "Viber",
        nameFieldDatabase: ["viber"],
        editorFieldNames: { viber: "Viber" },
        editorType: { viber: "input text" },
      },
      8: {
        type: "text",
        width: "auto",
        columnName: "Email",
        nameFieldDatabase: ["email"],
        editorFieldNames: { email: "Email" },
        editorType: { email: "input text" },
      },
      9: {
        type: "text",
        width: "auto",
        columnName: "table.users.col.languageId",
        enumeration: { languageId: CommonData.enumLanguage },
        nameFieldDatabase: ["languageId"],
        editorFieldNames: { languageId: "table.users.editor.languageId" },
        editorType: { languageId: "select" },
      },
    };
    return tableFields;
  };

  init5 = () => {
    /**
     * Description of the structure of the tasks table and its menu
     */
    const tableFields = {
      0: {
        type: "button",
        width: "150px",
        menu: CommonData.classicMenu,
        menuHeader: CommonData.classicMenuHeader,
      },
      1: {
        type: "line number",
        width: "auto",
        columnName: "№",
      },
      2: {
        type: "text",
        width: "auto",
        columnName: "table.tasks.col.delete",
        enumeration: { isDelete: CommonData.enumYesNo },
        nameFieldDatabase: ["isDelete"],
        editorFieldNames: { isDelete: "table.tasks.editor.delete" },
        editorType: { isDelete: "select" },
      },
      3: {
        type: "text",
        width: "auto",
        columnName: "table.tasks.col.userId",
        enumeration: { userId: CommonData.enumUser },
        nameFieldDatabase: ["userId"],
        editorFieldNames: { userId: "table.tasks.editor.userId" },
        editorType: { userId: "select" },
      },
      4: {
        type: "text",
        width: "auto",
        columnName: "table.tasks.col.task",
        enumeration: { task: CommonData.enumTask },
        nameFieldDatabase: ["task"],
        editorFieldNames: { task: "table.tasks.editor.task" },
        editorType: { task: "select" },
      },
    };
    return tableFields;
  };
}
