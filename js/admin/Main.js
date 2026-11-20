//Savvin Ruslan 2024-2025
import { localStoragePathGeneral, pageVideo } from "/js/admin/Constants.js";
import { getVarCss } from "/js/common/GeneralFunctions.js";
import { api } from "/js/common/ApiClient.js";
import { DynamicForm } from "/js/common/DynamicForm.js";
import { CommonData } from "/js/admin/CommonData.js";
import { scriptsArr } from "/js/admin/Constants.js";

let objectLanguage = null;
let objectDevice = null;
const moduleName = "main";

document.getElementById(moduleName).onload = async function () {
  window.isModalWindowInitialized = true;
  let scriptLocal = document.createElement("script");
  scriptLocal.id = "Localization";
  scriptLocal.async = true;
  scriptLocal.type = "module";
  scriptLocal.src = "/js/common/Localization.js";
  document.body.appendChild(scriptLocal);
  const dataRequest1 = new DynamicForm();
  dataRequest1.callPhpLoader = ["/src/GatewayPublicData.php", "reference/notAuthorization/fetch"];
  dataRequest1.queryParams = {
    tables: ["languages"],
  };
  const data = {
    action: "user/checkSession",
  };
  const dataTemp = {
    action: "templates/modalWindow",
  };
  const loadingPromises1 = [dataRequest1.load(), api.send(data, "/src/GatewayPublicData.php"), api.send(dataTemp, "/src/GatewayPublicData.php")];
  const [, result, result2] = await api.safeLoad(loadingPromises1);
  window.modalTemplate = result2.data;
  objectLanguage = dataRequest1.inPutData;
  const languageDefault = Object.keys(objectLanguage).find((key) => objectLanguage[key].isDefault === 1);
  if (!languageDefault) {
    alert("Error: Default language is missing. Please set one to continue.");
    throw new Error("");
  }
  window.language = api.getLocalStorage(localStoragePathGeneral + ":filters:language");
  if (!objectLanguage?.[window.language]) {
    const availableLangs = Object.keys(objectLanguage || {});
    window.language = availableLangs[0] || languageDefault;
    api.setLocalStorage(localStoragePathGeneral + ":filters:language", window.language);
  }
  const flag = objectLanguage[window.language].flag;
  document.querySelector("#buttonNav31").classList.add("flag-" + flag);
  const script1 = document.createElement("script");
  script1.async = true;
  script1.type = "module";
  script1.src = "/js/common/Navigation.js";
  document.body.appendChild(script1);
  if (!result.data.authorized) {
    authorizationFailed();
    return;
  }
  const vw = document.documentElement.clientWidth;
  const breakpoint = getVarCss("--media-max-width");
  if (vw <= breakpoint) {
    /*@media (max-width: 990px) /css/adminPart/page.css --media-max-width /css/adminPart/constans.css*/
    window.column = 1;
  } else {
    window.column = 2;
  }
  await CommonData.getDataFromServer();
  window.environment = CommonData.getData("environment");
  objectDevice = CommonData.getData("objectDevice");
  let script = document.createElement("script");
  script.id = "MainMenu";
  script.async = true;
  script.type = "module";
  script.src = "/js/admin/MainMenu.js";
  document.body.appendChild(script);
  const deviceDefault = Object.keys(objectDevice).find((key) => objectDevice[key].isDefault === 1);
  if (!deviceDefault) {
    alert("Error: Default device is missing. Please set one to continue.");
    throw new Error("");
  }

  window.deviceId = api.getLocalStorage(localStoragePathGeneral + ":filters:deviceId");
  if (!objectDevice?.[deviceId]) {
    const path = localStoragePathGeneral + ":filters:deviceId";
    api.setLocalStorage(path, deviceDefault);
    window.deviceId = deviceDefault;
  }
  document.getElementById("deviceName1").textContent = objectDevice[window.deviceId]["deviceName"];
  const loginContainer = document.querySelector(".login-container");
  loginContainer.classList.add("login-container-hidden");

  let favoritePage;
  const favoritePages = api.getLocalStorage(localStoragePathGeneral + ":filters:pages") || {};
  if (window.column === 1) {
    favoritePage = Object.keys(favoritePages).find((page) => favoritePages[page] === true);
  } else if (window.column === 2) {
    const videoScript = scriptsArr.find(([, pages]) => pages.includes(pageVideo));
    injectScript(videoScript[0]);
    favoritePage = Object.keys(favoritePages).find((page) => favoritePages[page] === true && page !== pageVideo);
  }
  const favoriteScriptEntry = scriptsArr.find(([, pages]) => pages.includes(favoritePage));
  const favoriteScriptId = favoriteScriptEntry ? favoriteScriptEntry[0] : null;
  document.querySelector("#indicatorBook").classList.remove("indicator-book-none");
  if (favoriteScriptId) {
    injectScript(favoriteScriptId);
    await new Promise((resolve) => setTimeout(resolve, 1000));
  }
  scriptsArr.forEach(([scriptId]) => {
    injectScript(scriptId);
  });
  startDeviceStatusTimer();
};

const injectScript = (scriptId) => {
  if (document.getElementById(scriptId)) {
    return;
  }
  const script = document.createElement("script");
  script.id = scriptId;
  script.type = "module";
  script.async = true;
  script.src = `/js/admin/${scriptId}.js`;
  document.body.appendChild(script);
};

const authorizationFailed = async () => {
  const loginContainer = document.querySelector(".login-container");
  loginContainer.classList.remove("login-container-hidden");
  const scriptLogin = document.createElement("script");
  scriptLogin.id = "Authorization";
  scriptLogin.async = true;
  scriptLogin.type = "module";
  scriptLogin.dataset.pageName = "page11";
  scriptLogin.src = "/js/admin/Authorization.js";
  document.body.appendChild(scriptLogin);
  document.body.classList.add("auth-page");
};

export const checkStatus = async () => {
  const dataRequest = new DynamicForm();
  dataRequest.queryParams = {};
  dataRequest.callPhpLoader = ["/src/GatewayAuthorizedData.php", "getTestResult/authorization/fetch"];
  const loadingPromises = [dataRequest.load()];
  await api.safeLoad(loadingPromises);

  for (let i = 0; i < dataRequest.inPutData.length; i++) {
    if (dataRequest.inPutData[i].id == 1) {
      const el = document.getElementById("tester1");
      el.textContent = "🔴";
      el.style.fontSize = "5mm";
      el.style.lineHeight = "1";
      const testResult = dataRequest.inPutData[i].testResult;
      if (testResult === "OK") {
        el.textContent = "🟢";
      }
    } else if (dataRequest.inPutData[i].id == 2) {
      const testJsonResult =
        typeof dataRequest.inPutData[i].testJsonResult === "string"
          ? JSON.parse(dataRequest.inPutData[i].testJsonResult)
          : dataRequest.inPutData[i].testJsonResult;

      const el = document.getElementById("deviceName22");
      el.textContent = "🔴";
      el.style.fontSize = "5mm";
      el.style.lineHeight = "1";
      for (let deviceId in testJsonResult) {
        if (Object.hasOwn(testJsonResult, deviceId)) {
          let time = testJsonResult[deviceId].time;
          if (deviceId === window.deviceId) {
            const isOnline = time >= 0 && time < 60;
            el.textContent = isOnline ? "🟢" : "🔴";
          }
        }
      }
    } else if (dataRequest.inPutData[i].id == 3) {
      const el = document.getElementById("tester2");
      el.style.lineHeight = "1";

      if (dataRequest.inPutData[i].testResult === "OK") {
        el.innerHTML = '<span style="font-size: 5mm;">🟢</span>';
      } else {
        const errors =
          typeof dataRequest.inPutData[i].testJsonResult === "string"
            ? JSON.parse(dataRequest.inPutData[i].testJsonResult)
            : dataRequest.inPutData[i].testJsonResult;
        const errorText = Array.isArray(errors) ? errors.join(", ") : "";
        el.innerHTML = `<span style="font-size: 5mm;">🔴</span> <span style="font-size: 14px;">${errorText}</span>`;
      }
    }
  }

  const { eventInit } = await import("/js/admin/Events.js");
  eventInit(true);
};

const startDeviceStatusTimer = () => {
  checkStatus();
  setInterval(checkStatus, 60000);
};
