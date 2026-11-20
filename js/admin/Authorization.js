//Savvin  Ruslan 2024
import { StructureMainMenu } from "/js/admin/structureOfTables/StructureMainMenu.js";
import { DynamicForm } from "/js/common/DynamicForm.js";
import { getModuleName, translateAndShow } from "/js/common/GeneralFunctions.js";
import { localStoragePathGeneral } from "/js/admin/Constants.js";
import { api } from "/js/common/ApiClient.js";
import { translate } from "/js/common/Localization.js";

const context = {};
let captchaSitekey;
let pageName = ["page11"];
const moduleName = getModuleName(import.meta.url);

document.querySelector("#" + moduleName).onload = async function () {
  const dataRequest1 = new DynamicForm();
  dataRequest1.callPhpLoader = ["/src/GatewayPublicData.php", "reference/notAuthorization/fetch"];
  dataRequest1.queryParams = {
    select: { 0: { action: "+", prefix: "generalSettings", columns: "captchaSitekey" } },
    tables: ["generalSettings"],
  };
  const dataRequest2 = new DynamicForm();
  dataRequest2.callPhpLoader = ["/src/GatewayPublicData.php", "reference/notAuthorization/fetch"];
  dataRequest2.queryParams = {
    select: { 0: { action: "+", prefix: "languages", columns: "*" } },
    tables: ["languages"],
    buildMap: [["id"], ["flag", "languageName", "adminPanel", "languageCode"]],
  };
  const loadingPromises = [dataRequest1.load(), dataRequest2.load()];
  await api.safeLoad(loadingPromises);
  captchaSitekey = dataRequest1.inPutData[0]["captchaSitekey"];
  document.querySelector("#blocklogin1").classList.remove("login-block-hidden");
  document.querySelector("#email").focus();
  const buttonNav3 = document.querySelector("#buttonNav3");
  buttonNav3.classList.remove("nav-div-hidden");
  buttonNav3.classList.add("nav-div");
  const structureMainMenu = new StructureMainMenu();
  context["objectLanguages"] = dataRequest2.inPutData;
  buttonNav3.__menuStorage = structureMainMenu.init(3, context);
  buttonNav3.querySelectorAll("*").forEach((el) => {
    el.__menuStorage = buttonNav3.__menuStorage;
  });
  const dropdown = document.querySelector("#dropdown");
  dropdown.addEventListener("click", (e) => actionClick(e));
  setTimeout(() => {
    const script = document.createElement("script");
    script.src = "https://www.google.com/recaptcha/enterprise.js?render=" + captchaSitekey;
    document.body.appendChild(script);
  }, 1500);
  await translateAndShow(pageName);
};

const actionClick = (event) => {
  const storage = event.target.__menuStorage ? event.target.__menuStorage : event.target.parentElement.__menuStorage;
  if (!storage || storage.context !== context) return;
  const menu = storage.menu;
  if (menu.action === "changeLanguage") {
    changeLanguage(menu.value);
    return;
  }
};

const changeLanguage = (id) => {
  Object.values(context["objectLanguages"]).forEach((item) => {
    document.querySelector("#buttonNav31").classList.remove(`flag-${item.flag}`);
  });
  const flag = context["objectLanguages"][id]?.flag;
  document.querySelector("#buttonNav31").classList.add(`flag-${flag}`);
  window.language = id;
  api.setLocalStorage(localStoragePathGeneral + ":filters:language", window.language);
  translate(document.querySelectorAll("[data-translate]"));
};

const getPassword = async (token) => {
  const data = {
    action: "user/getPassword",
    captcha: token,
    email: document.querySelector("#email").value,
  };
  const loadingPromises = [api.send(data, "/src/GatewayPublicData.php")];
  if (!(await api.safeLoad(loadingPromises))) return;
  document.querySelector("#blocklogin1").classList.add("login-block-hidden");
  document.querySelector("#blocklogin2").classList.remove("login-block-hidden");
  document.querySelector("#code").focus();
};

document.querySelector("#blocklogin1").addEventListener("submit", (event) => {
  event.preventDefault();
  window.grecaptcha.enterprise.ready(async () => {
    const token = await window.grecaptcha.enterprise.execute(captchaSitekey, {
      action: "LOGIN",
    });
    getPassword(token);
  });
});

document.querySelector("#blocklogin2").addEventListener("submit", (event) => {
  event.preventDefault();
  const btn = event.submitter;
  if (btn.id === "buttonSetAuthorization") {
    window.grecaptcha.enterprise.ready(async () => {
      const token = await window.grecaptcha.enterprise.execute(captchaSitekey, {
        action: "LOGIN",
      });
      const data = {
        action: "user/setAuthorization",
        captcha: token,
        email: document.querySelector("#email").value,
        code: document.querySelector("#code").value,
      };
      const loadingPromises = [api.send(data, "/src/GatewayPublicData.php")];
      if (!(await api.safeLoad(loadingPromises))) return;
      location.reload();
    });
  } else if (btn.id === "buttonPasswordReceived") {
    document.querySelector("#blocklogin1").classList.remove("login-block-hidden");
    document.querySelector("#blocklogin2").classList.add("login-block-hidden");
  }
});
