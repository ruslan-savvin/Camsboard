import { api } from "/js/common/ApiClient.js";

let captchaSitekey = "";

const initializeBooking = async () => {
  window.language = new URLSearchParams(window.location.search).get("language") || "1";
  const dataTemp = { action: "booking/getCaptchaSitekey" };
  const loadingPromises = [api.send(dataTemp, "/src/GatewayPublicData.php")];
  const [result] = await api.safeLoad(loadingPromises);
  captchaSitekey = result.data[0].captchaSitekey;
  setTimeout(() => {
    if (captchaSitekey) {
      const script = document.createElement("script");
      script.src = "https://www.google.com/recaptcha/enterprise.js?render=" + captchaSitekey;
      document.body.appendChild(script);
    }
  }, 1000);
  const tgChecked = document.getElementById("telegramChecked");
  const viberChecked = document.getElementById("viberChecked");
  const emailChecked = document.getElementById("emailChecked");
  let verificationCheckInterval = setInterval(async function () {
    const [result] = await api.safeLoad([api.send({ action: "guest/checkVerificationStatusChatCod" }, "/src/GatewayPublicData.php")]);
    if (result.data.chatCod) {
      const chatData = JSON.parse(result.data.chatCod, (key, value) => (key.startsWith("temp.") ? undefined : value));
      const toggleFlag = (element, value) => {
        const isVisible = value !== undefined && value !== null && String(value).trim() !== "";
        element.style.display = isVisible ? "inline-block" : "none";
        return isVisible;
      };
      const hasEmail = toggleFlag(emailChecked, chatData.email);
      const hasTg = toggleFlag(tgChecked, chatData.telegram);
      const hasViber = toggleFlag(viberChecked, chatData.viber);
      if (hasEmail && hasTg && hasViber) {
        clearInterval(verificationCheckInterval);
      }
    }
  }, 2500);
  document.getElementById("emailButton")?.addEventListener("click", handleButtonClick);
  document.getElementById("telegram")?.addEventListener("click", handleButtonClick);
  document.getElementById("viber")?.addEventListener("click", handleButtonClick);
};

initializeBooking();

const handleButtonClick = async (event) => {
  event.preventDefault();
  let dataTemp;
  const clickedId = event.currentTarget.id;
  let captchaToken = "";
  if (typeof grecaptcha !== "undefined") {
    captchaToken = await window.grecaptcha.enterprise.execute(captchaSitekey, { action: "LOGIN" });
  }
  if (clickedId === "emailButton") {
    dataTemp = {
      action: "guest/sendEmailVerification",
      captcha: captchaToken,
      email: document.getElementById("email").value,
      languageId: window.language,
    };
    const [result] = await api.safeLoad([api.send(dataTemp, "/src/GatewayPublicData.php")]);
    const messageText = result.message[0].join("");
    showToast(messageText);
  } else if (clickedId === "telegram") {
    dataTemp = {
      action: "guest/ensureUserSession",
      captcha: captchaToken,
      languageId: window.language,
    };
    const [result] = await api.safeLoad([api.send(dataTemp, "/src/GatewayPublicData.php")]);
    window.open(`https://t.me/Great_Apart_bot?start=${result.data}`, "_blank");
  } else if (clickedId === "viber") {
    dataTemp = {
      action: "guest/ensureUserSession",
      captcha: captchaToken,
      languageId: window.language,
    };
    const [result] = await api.safeLoad([api.send(dataTemp, "/src/GatewayPublicData.php")]);
    window.open(`viber://pa?chatURI=greatapart&context=${result.data}`, "_blank");
  } else {
    return;
  }
};

const showToast = async (message) => {
  const toast = document.createElement("div");
  toast.innerText = message;
  Object.assign(toast.style, {
    position: "fixed",
    top: "30px",
    left: "50%",
    transform: "translateX(-50%) translateY(-20px)",
    backgroundColor: "#eff6ff",
    color: "#2563eb",
    padding: "14px 28px",
    borderRadius: "12px",
    fontSize: "14px",
    fontWeight: "600",
    boxShadow: "0 10px 25px -5px rgba(37, 99, 235, 0.05), 0 8px 10px -6px rgba(37, 99, 235, 0.05)",
    border: "1px solid #bfdbfe",
    zIndex: "99999",
    opacity: "0",
    transition: "all 0.3s ease",
    fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
    textAlign: "center",
    minWidth: "280px",
    maxWidth: "90vw",
  });
  document.body.appendChild(toast);
  setTimeout(() => {
    toast.style.opacity = "1";
    toast.style.transform = "translateX(-50%) translateY(0)";
  }, 10);
  setTimeout(() => {
    toast.style.opacity = "0";
    toast.style.transform = "translateX(-50%) translateY(-20px)";
    setTimeout(() => toast.remove(), 300);
  }, 4000);
};
