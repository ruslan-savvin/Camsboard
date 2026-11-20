//Savvin Ruslan 2024
import { scriptsArr } from "/js/admin/Constants.js";
import { getModuleName, translateAndShow } from "/js/common/GeneralFunctions.js";
import { CommonData } from "/js/admin/CommonData.js";
export { videoInit };

const objectDevice = CommonData.getData("objectDevice");
const objectGeneralSettings = CommonData.getData("objectGeneralSettings");
const context = {};
const moduleName = getModuleName(import.meta.url);
let pageName = scriptsArr.find((item) => item[0] === moduleName)[1];
document.getElementById(moduleName).onload = async function () {
  videoInit(false);
};

const videoInit = async (refresh) => {
  document.getElementById("deviceName1").textContent = objectDevice[window.deviceId]["deviceName"];
  const iframe = document.getElementById("iframe1");
  const streamKey = objectDevice[window.deviceId]["streamKey"];
  const width = objectDevice[window.deviceId]["width"];
  const height = objectDevice[window.deviceId]["height"];
  if (streamKey) {
    iframe.src = objectGeneralSettings[0].domain + ":8889/" + streamKey;
  } else {
    iframe.src = "";
  }
  await translateAndShow(pageName);

  if (width && height) {
    iframe.style.height = `${iframe.offsetWidth * (height / width)}px`;
  }
};
