//Savvin Ruslan 2025
export class StructureSystemManager {
  constructor() {}
  init = (version) => {
    const methodName = `init${version}`;
    return this[methodName]();
  };
  init1 = () => {
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
        menuHeader: menuHeader,
      },
      1: {
        type: "text",
        width: "auto",
        columnName: "table.logs.col.date",
        nameFieldDatabase: ["d"],
      },
      2: {
        type: "text",
        width: "auto",
        columnName: "table.logs.col.information",
        nameFieldDatabase: ["i"],
        preLine: true,
      },
    };
    return tableFields;
  };
}
