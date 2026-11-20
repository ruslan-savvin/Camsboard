//Savvin Ruslan 2025
import { DynamicForm } from "/js/common/DynamicForm.js";
import { api } from "/js/common/ApiClient.js";

export class CommonData {
  static objectLanguages = {};
  static objectGeneralSettings = {};
  static environment = "";

  static enumYesNo = {};
  static enumNumbers = {};

  static getData = (objName) => {
    return CommonData[objName];
  };

  static getDataFromServer = async () => {
    const dataRequest1 = new DynamicForm();
    dataRequest1.callPhpLoader = ["/src/GatewayAuthorizedData.php", "reference/authorization/fetch"];
    dataRequest1.queryParams = {
      select: { 0: { action: "+", prefix: "devices", columns: "*" } },
      tables: ["devices"],
      where: "WHERE devices.isDelete <> 1",
      buildMap: [["id"], ["deviceName", "isDelete", "isDefault", "streamKey", "width", "height"]],
    };

    const dataRequest2 = new DynamicForm();
    dataRequest2.callPhpLoader = ["/src/GatewayAuthorizedData.php", "reference/authorization/fetch"];
    dataRequest2.queryParams = {
      select: { 0: { action: "+", prefix: "languages", columns: "*" } },
      tables: ["languages"],
      where: "WHERE languages.isDelete <> 1",
      buildMap: [["id"], ["flag", "languageName", "adminPanel", "languageCode"]],
    };

    const dataRequest6 = new DynamicForm();
    dataRequest6.callPhpLoader = ["/src/GatewayAuthorizedData.php", "reference/authorization/fetch"];
    dataRequest6.queryParams = {
      select: { 0: { action: "+", prefix: "users", columns: "*" }, 1: { columns: "CONCAT(users.name, ' ', users.surname) AS nameSurname" } },
      tables: ["users"],
      where: "WHERE users.isDelete <> 1 AND users.role = 2",
      buildMap: [["id"], ["nameSurname"]],
    };

    const dataRequest7 = new DynamicForm();
    dataRequest7.callPhpLoader = ["/src/GatewayAuthorizedData.php", "reference/authorization/fetch"];
    dataRequest7.queryParams = {
      select: { 0: { action: "+", prefix: "generalSettings", columns: "*" } },
      tables: ["generalSettings"],
    };

    const dataRequest8 = new DynamicForm();
    dataRequest8.callPhpLoader = ["/src/GatewayAuthorizedData.php", "environment/authorization/fetch"];

    const loadingPromises = [dataRequest1.load(), dataRequest2.load(), dataRequest6.load(), dataRequest7.load(), dataRequest8.load()];
    await api.safeLoad(loadingPromises);

    CommonData.objectDevice = dataRequest1.inPutData;
    CommonData.objectLanguages = dataRequest2.inPutData;
    CommonData.objectUsers = dataRequest6.inPutData;
    CommonData.objectGeneralSettings = dataRequest7.inPutData;
    CommonData.environment = dataRequest8.inPutData[0].environment;

    CommonData.enumLanguage = Object.freeze(CommonData.getEnumeration(CommonData.objectLanguages, "languageCode"));
    CommonData.enumUser = Object.freeze(CommonData.getEnumeration(CommonData.objectUsers, "nameSurname"));
    CommonData.enumYesNo = Object.freeze({
      0: "enumeration.yesNo.no",
      1: "enumeration.yesNo.yes",
    });
    CommonData.enumYesNoPending = Object.freeze({
      0: "enumeration.yesNoPending.pending",
      1: "enumeration.yesNo.no",
      2: "enumeration.yesNo.yes",
    });

    CommonData.enumNumbers = Object.freeze({
      0: "0",
      1: "1",
      2: "2",
      3: "3",
      4: "4",
      5: "5",
    });

    CommonData.enumTask = Object.freeze({
      1: "Human object detected",
      2: "Detection check error",
      3: "new task 2",
      4: "new task 3",
    });

    CommonData.classicMenuHeader = {
      0: {
        action: "add",
        label: "global.menu.add",
      },
      1: {
        action: "save",
        label: "global.menu.save",
        confirmKey: "forma.global.button.save",
      },
    };
    CommonData.classicMenu = {
      0: {
        action: "del",
        label: "global.menu.delete",
      },
      1: {
        action: "edit",
        label: "global.menu.edit",
      },
    };
  };

  static getEnumeration = (data, columnName) => {
    return Object.fromEntries(Object.entries(data).map(([id, d]) => [id, d[columnName]]));
  };
}
