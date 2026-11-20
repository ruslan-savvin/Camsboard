//Savvin Ruslan 2025
import { scriptsArr } from "/js/admin/Constants.js";
import { StructureSystemManager } from "/js/admin/structureOfTables/StructureSystemManager.js";
import { TableManagement } from "/js/common/TableManagement.js";
import { reportStatus, getModuleName, translateAndShow } from "/js/common/GeneralFunctions.js";
import { api } from "/js/common/ApiClient.js";

const context = {};
const moduleName = getModuleName(import.meta.url);
let pageName = scriptsArr.find((item) => item[0] === moduleName)[1];

document.getElementById(moduleName).onload = function () {
  const data = { refresh: false };
  init(data);
};

const init = async (data) => {
  const table = new TableManagement();
  table.callBackAction = callBackAction;
  table.refresh = data.refresh;
  table.additionalInfo = {};
  table.queryParams = {
    limit: "LIMIT ",
  };
  table.setTableNode("log");
  table.callPhpLoader = ["/src/GatewayAuthorizedData.php", "log/fetch"];
  const tableStructure = new StructureSystemManager();
  table.tableFields = tableStructure.init(1);
  const loadingPromises = [table.loadData()];
  const [result] = await api.safeLoad(loadingPromises);
  if (!result) return;
  await translateAndShow(pageName);
  if (data.refresh) return;
};

const callBackAction = async (menuStorage) => {
  if (menuStorage.menu.action === "refresh") {
    const data = { refresh: true };
    const indicator = document.querySelector("#indicatorBook");
    try {
      indicator.classList.remove("indicator-book-none");
      await init(data);
    } finally {
      indicator.classList.add("indicator-book-none");
    }
  }
};
