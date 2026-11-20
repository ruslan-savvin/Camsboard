//Savvin Ruslan 2025
import { modalWindow } from "/js/common/ModalWindow.js";
import { translate } from "/js/common/Localization.js";
import { pageVideo } from "/js/admin/Constants.js";

export { reportStatus, getObjectId, getVarCss, getModuleName, formatDataValue, translateAndShow };

const globalAppState = {
  isFirstPageOpened: false,
};

const reportStatus = async (data) => {
  let message = data.message
    ? Object.values(data.message).flatMap((msg, index, array) => {
        return index < array.length - 1 ? [...msg, "\n"] : msg;
      })
    : data;
  if (Array.isArray(message)) {
    message = JSON.stringify(message);
  }
  const mW = new modalWindow();
  mW.inPutData = { 0: { message: message } };
  mW.row = 0;
  mW.fields = {
    0: {
      nameFieldDatabase: ["message"],
      editorFieldNames: { message: "" },
      editorType: { message: "p" },
    },
  };
  mW.buttons = ["modalWindow.button.ok"];
  mW.header = "modalWindow.inscription.info";
  //mW.header = "modalWindow.inscription.error";
  mW.setSettings();
  document.querySelector("#indicatorBook").classList.add("indicator-book-none");
  if (!(await mW.show())) return;
};

const getObjectId = () => {
  return Math.random().toString().replace(/[.]/g, "");
};

const getVarCss = (stringVar) => {
  const rootStyle = getComputedStyle(document.documentElement);
  return parseInt(rootStyle.getPropertyValue(stringVar).trim());
};

const getModuleName = (url) => url.match(/\/([^/]+)\.js(?:\?|$)/)?.[1];

const formatDataValue = (value, template, nameFieldDatabase, returnJSON = true) => {
  if (!Array.isArray(value) || !nameFieldDatabase || (typeof nameFieldDatabase === "string" && nameFieldDatabase.trim() === "")) return value;
  const fieldsArray = Array.isArray(nameFieldDatabase) ? nameFieldDatabase : [nameFieldDatabase];
  const resultArray = value.flatMap((item, index) => {
    const key = fieldsArray[index];
    const config = template?.[key];
    if (!config || !Array.isArray(config.separators)) return item;
    return config.separators.map((part) => {
      if (part === "{DATA_HERE}") return item;
      if (typeof part === "string" && part.includes("{CLASS__HERE}") && Array.isArray(config.where)) {
        const data = { part, value, key, nameFieldDatabase, conditions: config.where };
        return formatClassValue(data);
      }
      return part;
    });
  });
  if (returnJSON) {
    return JSON.stringify(resultArray).replace(/"/g, "'");
  } else {
    return resultArray;
  }
};

const formatClassValue = (data) => {
  const { part, value, key, nameFieldDatabase, conditions } = data;
  const rowData = Object.fromEntries(nameFieldDatabase.map((name, index) => [name, value[index]]));
  let finalClassName = "neutral";
  for (const rule of conditions) {
    const conditionFn = conditionsMap[rule.condition];
    if (conditionFn && conditionFn(rowData, key, rule)) {
      finalClassName = rule.className;
      break;
    }
  }
  return part.replace(/{CLASS__HERE}/g, finalClassName);
};

const conditionsMap = {
  greaterThanZero: (data, key) => Number(data[key]) > 0,
  lessThanZero: (data, key) => Number(data[key]) < 0,
  equalToZero: (data, key) => Number(data[key]) === 0,
  isApproved: (data, key) => String(data[key]).trim() === "Approved",
  isRefunded: (data, key) => String(data[key]).trim() === "Refunded",
  isVoided: (data, key) => String(data[key]).trim() === "Voided",
  isWaitingAuth: (data, key) => String(data[key]).trim() === "WaitingAuthComplete",
  isOverdue: (data, key, rule) => {
    if (!data || !data[key]) return false;
    //balance_to_pay < 0 and datePayment < Current date
    const [day, month, year] = data[key].split("-");
    const paymentDate = new Date(year, month - 1, day);
    const balance = Number(data[rule.extraField?.balance]);
    return paymentDate < new Date() && balance < 0;
  },
};

const translateAndShow = async (pageName) => {
  const translationPromises = pageName.map(async (page) => {
    const parent = document.querySelector("#" + page);
    const newItems = parent.querySelectorAll("[data-translate]");
    await translate(newItems);
    if (page === pageVideo && window.column === 2) {
      parent.classList.remove("page-hidden");
      return;
    }
    if (!globalAppState.isFirstPageOpened) {
      globalAppState.isFirstPageOpened = true;
      parent.classList.remove("page-hidden");
      document.querySelector("#indicatorBook").classList.add("indicator-book-none");
    }
  });
  await Promise.all(translationPromises);
};
