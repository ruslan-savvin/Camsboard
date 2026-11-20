//Savvin Ruslan 2024
import { scriptsArr } from "/js/admin/Constants.js";
import { StructureReferences } from "/js/admin/structureOfTables/StructureReferences.js";
import { TableManagement } from "/js/common/TableManagement.js";
import { api } from "/js/common/ApiClient.js";
import { getModuleName, reportStatus, translateAndShow } from "/js/common/GeneralFunctions.js";

const context = {};
const moduleName = getModuleName(import.meta.url);
let pageName = scriptsArr.find((item) => item[0] === moduleName)[1];
document.getElementById(moduleName).onload = async function () {
  const data = { refresh: false };
  const tableStructure = new StructureReferences(context);

  const table1 = new TableManagement();
  table1.refresh = data.refresh;
  table1.additionalInfo = {};
  table1.queryParams = {
    select: { 0: { action: "+", prefix: "devices", columns: "*" } },
    tables: ["devices"],
  };
  table1.setTableNode("devices");
  table1.callPhpLoader = ["/src/GatewayAuthorizedData.php", "reference/authorization/fetch"];
  table1.callPhpSave = ["/src/GatewayAuthorizedData.php", "reference/devices/authorization/save"];
  table1.savingSettings = {
    ignoredKeys: { edit: ["filterData"], add: ["filterData"] },
  };
  table1.tableFields = tableStructure.init(1);

  const table2 = new TableManagement();
  table2.refresh = data.refresh;
  table2.additionalInfo = {};
  table2.queryParams = {
    select: { 0: { action: "+", prefix: "languages", columns: "*" } },
    tables: ["languages"],
    orderBy: "ORDER BY languages.ordering",
  };
  table2.setTableNode("languages");
  table2.callPhpLoader = ["/src/GatewayAuthorizedData.php", "reference/authorization/fetch"];
  table2.callPhpSave = ["/src/GatewayAuthorizedData.php", "reference/authorization/save"];
  table2.savingSettings = {
    ignoredKeys: { edit: ["filterData"], add: ["filterData"] },
  };
  table2.tableFields = tableStructure.init(2);

  const table4 = new TableManagement();
  table4.refresh = data.refresh;
  table4.additionalInfo = {};
  table4.queryParams = {
    select: { 0: { action: "+", prefix: "users", columns: "*" } },
    tables: ["users"],
  };
  table4.setTableNode("users");
  table4.callPhpLoader = ["/src/GatewayAuthorizedData.php", "reference/authorization/fetch"];
  table4.callPhpSave = ["/src/GatewayAuthorizedData.php", "reference/authorization/save"];
  table4.callBackAction = callBackAction;
  table4.savingSettings = {
    ignoredKeys: { edit: ["filterData", "chat"], add: ["filterData", "chat"] },
  };
  table4.tableFields = tableStructure.init(4);

  const table5 = new TableManagement();
  table5.refresh = data.refresh;
  table5.additionalInfo = {};
  table5.queryParams = {
    select: { 0: { action: "+", prefix: "", columns: "*" } },
    tables: ["tasks"],
  };
  table5.setTableNode("tasks");
  table5.callPhpLoader = ["/src/GatewayAuthorizedData.php", "reference/authorization/fetch"];
  table5.callPhpSave = ["/src/GatewayAuthorizedData.php", "reference/authorization/save"];
  table5.savingSettings = {
    ignoredKeys: { edit: ["filterData"], add: ["filterData"] },
  };
  table5.tableFields = tableStructure.init(5);

  const loadingPromises = [table1.loadData(), table2.loadData(), table4.loadData(), table5.loadData()];
  const results = await api.safeLoad(loadingPromises);
  await translateAndShow(pageName);
};

const callBackAction = async (data) => {
  const action = data.menu.action;
  /* if (!action || action === "onAfterTableCreate") return;
  if (action === "onBeforeTableCreate") {
  }  
  }*/
};
