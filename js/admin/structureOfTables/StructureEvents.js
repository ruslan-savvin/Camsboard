//Savvin Ruslan 2025
import { CommonData } from "/js/admin/CommonData.js";

export class StructureEvents {
  constructor(context) {
    this.context = context;
  }

  init = (version) => {
    const methodName = `init${version}`;
    return this[methodName]();
  };
  init1 = () => {
    /**
     * Description of the structure of the device events table and its menu
     */
    const menu = {
      0: { action: "showFoto", extraParameter: ["jpg"], label: "global.menu.showFoto" },
      1: { action: "showFoto", extraParameter: ["mp4"], label: "Video" },
    };

    const menuHeader = {
      0: {
        action: "refresh",
        label: "global.menu.refresh",
        onBeforeModalOpen: true,
      },
    };

    const tableFields = {
      0: {
        type: "button",
        width: "150px",
        menu: menu,
        menuHeader: menuHeader,
      },
      1: {
        type: "line number",
        width: "auto",
        columnName: "№",
      },
      2: {
        type: "text",
        width: "auto",
        columnName: "table.deviceEvents.col.id",
        nameFieldDatabase: ["id"],
      },
      3: {
        type: "text",
        width: "auto",
        columnName: "table.deviceEvents.col.eventTime",
        nameFieldDatabase: ["eventTime"],
      },
      4: {
        type: "text",
        width: "auto",
        columnName: "table.deviceEvents.col.sceneScore",
        nameFieldDatabase: ["sceneScore"],
      },
      5: {
        type: "text",
        width: "auto",
        columnName: "table.deviceEvents.col.isPhotoReady",
        nameFieldDatabase: ["isPhotoReady", "isVideoReady"],
        template: {
          isPhotoReady: { separators: ["foto: ", "{DATA_HERE}", "\n"] },
          isVideoReady: { separators: ["video: ", "{DATA_HERE}", "\n"] },
        },
        preLine: true,
      },
      6: {
        type: "text",
        width: "auto",
        columnName: "table.deviceEvents.col.isHumanChecked",
        nameFieldDatabase: ["isHumanChecked", "confidence", "humanCheckedFrame"],
        enumeration: { isHumanChecked: CommonData.enumYesNoPending },
        template: {
          isHumanChecked: { separators: ["{DATA_HERE}", " "] },
          humanCheckedFrame: { separators: ["{DATA_HERE}", "\n"] },
          confidence: { separators: ["{DATA_HERE}", " %", "\n"] },
        },
        preLine: true,
      },
      7: {
        type: "text",
        width: "auto",
        columnName: "-",
        nameFieldDatabase: ["elapsed", "filesChecked"],
        template: {
          elapsed: { separators: ["{DATA_HERE}", " sek/frame", "\n"] },
          filesChecked: { separators: ["{DATA_HERE}", " frame", "\n"] },
        },
        preLine: true,
      },
      8: {
        type: "skip",
        width: "auto",
        columnName: "-",
        nameFieldDatabase: ["patch"],
        editorFieldNames: { patch: "-" },
        editorType: { patch: "img" },
      },
    };
    return tableFields;
  };
}
