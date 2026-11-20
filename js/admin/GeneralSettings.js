//Savvin Ruslan 2024
import { modalWindow } from "/js/common/ModalWindow.js";
import { DynamicForm } from "/js/common/DynamicForm.js";
import { api } from "/js/common/ApiClient.js";
import { getModuleName, translateAndShow } from "/js/common/GeneralFunctions.js";
import { scriptsArr } from "/js/admin/Constants.js";

const localizationMap = {
  domain: "forma.systemSettings.inscription.domain",
  testDomain: "forma.systemSettings.inscription.testDomain",
  mailHost: "forma.systemSettings.inscription.mailHost",
  mailUser: "forma.systemSettings.inscription.mailUser",
  mailPass: "forma.systemSettings.inscription.mailPass",
  telegramBotIp: "forma.systemSettings.inscription.telegramBotIp",
  viberBotIp: "forma.systemSettings.inscription.viberBotIp",
  ffmpegService: "ffmpeg service",
  detectService: "detect service",
  timerService: "timer service",
  timerCleaner: "timer cleaner",
  timerCopier: "timer copier",
  timerDetector: "timer detector",
  timerTester: "timer tester",
};

const moduleName = getModuleName(import.meta.url);
let pageName = scriptsArr.find((item) => item[0] === moduleName)[1];

document.getElementById(moduleName).onload = async function () {
  const GeneralSettingsForm = new DynamicForm();
  GeneralSettingsForm.additionalInfo = { changed: 3 };
  GeneralSettingsForm.queryParams = {
    select: { 0: { action: "+", prefix: "generalSettings", columns: "*" } },
    tables: ["generalSettings"],
  };
  GeneralSettingsForm.mountPoint = "page5-fields";
  GeneralSettingsForm.callPhpLoader = ["/src/GatewayAuthorizedData.php", "reference/authorization/fetch"];
  GeneralSettingsForm.callPhpSave = ["/src/GatewayAuthorizedData.php", "reference/GeneralSettings/authorization/save"];
  GeneralSettingsForm.textarea = ["ffmpegService", "detectService", "timerService", "timerCleaner", "timerCopier", "timerDetector", "timerTester"];

  GeneralSettingsForm.localizationMap = localizationMap;
  GeneralSettingsForm.saveAttribute = "saveattribute" + pageName;
  const loadingPromises = [GeneralSettingsForm.load()];
  if (!(await api.safeLoad(loadingPromises))) return;
  GeneralSettingsForm.render();

  const saveBtn = document.querySelector("#buttonSaveGeneralSettings");
  if (saveBtn) {
    saveBtn.onclick = async () => {
      buttonSaveGeneralSettingsOnclick(GeneralSettingsForm);
    };
  }
  await translateAndShow(pageName);
};

const buttonSaveGeneralSettingsOnclick = async (GeneralSettingsForm) => {
  const mW = new modalWindow();
  mW.inPutData = { 0: { message: JSON.stringify(["forma.global.button.save", "?"]) } };
  mW.row = 0;
  mW.fields = {
    0: {
      type: "p",
      nameFieldDatabase: ["message"],
      editorFieldNames: { message: "" },
      editorType: { message: "p" },
    },
  };
  mW.buttons = ["global.button.yes", "global.button.no"];
  mW.header = "modalWindow.inscription.question";
  mW.setSettings();
  if (!(await mW.show())) return;
  await GeneralSettingsForm.save();
};
