export class ApiClient {
  #defaultHeaders;
  constructor() {
    this.#defaultHeaders = {
      credentials: "same-origin",
    };
    Object.preventExtensions(this);
  }
  async send(data, url) {
    const isFormData = data?.has?.("formData");
    const body = isFormData ? data : JSON.stringify(data);
    const config = isFormData ? { method: "POST", body } : this.#prepareConfig(data);
    const response = await fetch(url, config);
    return await this.#actionesponse(response);
  }

  #prepareConfig(data) {
    const config = {
      ...this.#defaultHeaders,
      method: "POST",
    };
    if (data instanceof FormData) {
      config.body = data;
    } else {
      config.headers = {
        ...this.#defaultHeaders.headers,
        "Content-Type": "application/json",
      };
      const payload = {
        ...data,
      };
      config.body = JSON.stringify(payload);
    }
    return config;
  }

  async #actionesponse(response) {
    const contentType = response.headers.get("Content-Type") || "";
    if (contentType.includes("application/pdf")) {
      return await response.blob();
    }
    if (contentType.includes("application/json")) {
      const json = await response.json();
      if (!response.ok) throw json;
      return json;
    }
    if (contentType.includes("text/html")) {
      return await response.text();
    }
    return response;
  }

  async safeLoad(promises) {
    try {
      return await Promise.all(promises);
    } catch (error) {
      if (error instanceof Error) {
        console.error(error.stack);
      } else {
        if (window.isModalWindowInitialized) {
          const { reportStatus } = await import("/js/common/GeneralFunctions.js");
          reportStatus(error);
        } else {
          console.error(error);
        }
        if (error.message && Array.isArray(error.message)) {
          throw error.message.join("\n");
        }
      }
      throw error;
    }
  }

  getLocalStorage = (path) => {
    const parts = path.split(":");
    let current = JSON.parse(localStorage.getItem(parts[0]) || "{}");
    for (let i = 1; i < parts.length; i++) {
      if (current && typeof current === "object" && parts[i] in current) {
        current = current[parts[i]];
      } else {
        return null;
      }
    }
    return current;
  };

  setLocalStorage = (path, value) => {
    const parts = path.split(":");
    const rootKey = parts[0];
    let fullData = JSON.parse(localStorage.getItem(rootKey) || "{}");

    const updateDeep = (obj, pathParts, val) => {
      const key = pathParts[0];
      if (pathParts.length === 1) {
        if (typeof val === "object" && val !== null && typeof obj[key] === "object" && obj[key] !== null) {
          obj[key] = { ...obj[key], ...val };
        } else {
          obj[key] = val;
        }
        return;
      }
      if (!obj[key] || typeof obj[key] !== "object") {
        obj[key] = {};
      }
      updateDeep(obj[key], pathParts.slice(1), val);
    };

    updateDeep(fullData, parts.slice(1), value);
    localStorage.setItem(rootKey, JSON.stringify(fullData));
  };
}

export const api = new ApiClient();
