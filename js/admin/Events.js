//Savvin Ruslan 2024
import { scriptsArr } from "/js/admin/Constants.js";
import { StructureEvents } from "/js/admin/structureOfTables/StructureEvents.js";
import { TableManagement } from "/js/common/TableManagement.js";
import { api } from "/js/common/ApiClient.js";
import { getModuleName, reportStatus, translateAndShow } from "/js/common/GeneralFunctions.js";
import { CommonData } from "/js/admin/CommonData.js";
export { eventInit };

const objectDevice = CommonData.getData("objectDevice");
const context = {};
const moduleName = getModuleName(import.meta.url);
let pageName = scriptsArr.find((item) => item[0] === moduleName)[1];

document.getElementById(moduleName).onload = async function () {
  eventInit(false);
};

const eventInit = async (refresh) => {
  const tableStructure = new StructureEvents(context);
  const table1 = new TableManagement();
  table1.topOffset = 25;
  table1.callBackAction = callBackAction;
  table1.refresh = refresh;
  table1.additionalInfo = {};
  table1.queryParams = {
    select: {
      0: { action: "+", prefix: "deviceEvents", columns: "*" },
      1: { action: "+", prefix: "", columns: "CONCAT('/storage/event-', deviceEvents.device , '/', deviceEvents.id, '/') AS patch " },
    },
    tables: ["deviceEvents"],
    where: "WHERE deviceEvents.device IN (" + window.deviceId + ")",
    orderBy: "ORDER BY deviceEvents.id DESC",
  };
  table1.setTableNode("events");
  table1.callPhpLoader = ["/src/GatewayAuthorizedData.php", "reference/authorization/fetch"];
  table1.callPhpSave = ["/src/GatewayAuthorizedData.php", "reference/authorization/save"];
  table1.savingSettings = {
    ignoredKeys: { edit: ["filterData"], add: ["filterData"] },
  };
  table1.tableFields = tableStructure.init(1);
  const loadingPromises = [table1.loadData()];
  const results = await api.safeLoad(loadingPromises);
  document.getElementById("deviceName21").textContent = objectDevice[window.deviceId]["deviceName"];
  await translateAndShow(pageName);
};

const callBackAction = async (menuStorage) => {
  if (menuStorage.menu.action === "refresh") {
    const data = { refresh: true };
    const indicator = document.querySelector("#indicatorBook");
    try {
      indicator.classList.remove("indicator-book-none");
      await eventInit(data);
    } finally {
      indicator.classList.add("indicator-book-none");
    }
  }
};
