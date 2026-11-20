import { DynamicForm } from "/js/common/DynamicForm.js";
import { api } from "/js/common/ApiClient.js";
export { translate };

const localTranslationCache = {};
const translate = async (elements) => {
  let keysForQuery = [];
  elements.forEach((element) => {
    getListKeys(element.dataset.translate).forEach((key) => {
      const isMissing = localTranslationCache[key] === undefined || localTranslationCache[key][window.language] === undefined;
      if (isMissing && /^[a-zA-Z][a-zA-Z0-9]*\.[a-zA-Z0-9.]+$/.test(key) && !keysForQuery.includes(key)) {
        keysForQuery.push(key);
      }
    });
  });
  if (keysForQuery.length > 0) {
    keysForQuery.forEach((key) => {
      if (localTranslationCache[key] === undefined) localTranslationCache[key] = {};
      if (localTranslationCache[key][window.language] === undefined) {
        localTranslationCache[key][window.language] = { textValue: key };
      }
    });
    const sqlInQueryString = keysForQuery.map((k) => `'${k}'`).join(",");
    const dataRequest = new DynamicForm();
    dataRequest.callPhpLoader = ["/src/GatewayPublicData.php", "reference/notAuthorization/fetch"];
    dataRequest.queryParams = {
      select: { 0: { action: "+", prefix: "localization", columns: "textKey,textValue,languageId" } },
      tables: ["localization"],
      where: `WHERE localization.textKey IN (${sqlInQueryString}) AND localization.languageId IN (${window.language})`,
      buildMap: [["textKey", "languageId"], ["textValue"]],
    };
    await api.safeLoad([dataRequest.load()]);
    if (dataRequest.inPutData) {
      Object.keys(dataRequest.inPutData).forEach((key) => {
        localTranslationCache[key] = Object.assign(localTranslationCache[key] || {}, dataRequest.inPutData[key]);
      });
    }
  }
  elements.forEach((element) => {
    const targetElement = element.querySelector("[data-translate]") || element;
    if (!targetElement?.dataset?.translate) return;
    const finalValue = setValues(targetElement.dataset.translate);
    if (["INPUT", "TEXTAREA"].includes(targetElement.tagName)) {
      targetElement.value = finalValue;
    } else {
      const prop = /<span/i.test(String(finalValue)) ? "innerHTML" : "textContent";
      targetElement[prop] = finalValue;
    }
  });
};

const getListKeys = (input) => {
  if (typeof input === "string" && input.trim().startsWith("[")) {
    try {
      return JSON.parse(input.replace(/'/g, '"'));
    } catch {
      return [String(input).trim()];
    }
  } else {
    return [String(input).trim()];
  }
};

const parseArrayPipeline = (array) => {
  let result = "";
  const currentLang = String(window.language);
  let i = 0;
  while (i < array.length) {
    const item = array[i];
    if (typeof item === "string" || typeof item === "number") {
      const strItem = String(item).trim();
      if (/^[a-zA-Z][a-zA-Z0-9_]*\.[a-zA-Z0-9_.]+$/.test(strItem)) {
        let textTemplate = (localTranslationCache[strItem] && localTranslationCache[strItem][currentLang]?.textValue) || strItem;
        const placeholderCount = (textTemplate.match(/%s/g) || []).length;
        if (placeholderCount > 0) {
          const params = array.slice(i + 1, i + 1 + placeholderCount);
          params.forEach((param) => {
            textTemplate = textTemplate.replace("%s", param);
          });
          i += 1 + placeholderCount;
        } else {
          i++;
        }
        result += textTemplate;
      } else {
        result += item;
        i++;
      }
    } else {
      result += setValues(item);
      i++;
    }
  }
  return result;
};

const setValues = (input) => {
  let parsed = input;
  if (typeof input === "string" && input.trim().startsWith("[")) {
    try {
      parsed = JSON.parse(input.replace(/'/g, '"'));
    } catch {
      parsed = input;
    }
  }
  if (Array.isArray(parsed)) {
    return parseArrayPipeline(parsed);
  }
  const k = String(parsed).trim();
  if (/^[a-zA-Z][a-zA-Z0-9_]*\.[a-zA-Z0-9_.]+$/.test(k)) {
    const currentLang = String(window.language);
    return (localTranslationCache[k] && localTranslationCache[k][currentLang]?.textValue) || k;
  }
  return parsed;
};
