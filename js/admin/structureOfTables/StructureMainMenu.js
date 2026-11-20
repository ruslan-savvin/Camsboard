import { pageVideo } from "/js/admin/Constants.js";

export class StructureMainMenu {
  constructor() {}
  init = (version, context) => {
    const methodName = `init${version}`;
    return this[methodName](context);
  };
  init1 = (context) => {
    const menu2 = {
      0: {
        action: pageVideo,
        label: "global.nav.pageVideo",
      },
      1: {
        action: "page4",
        label: "global.nav.page4",
      },
      2: {
        action: "page5",
        label: "global.nav.page5",
      },
      3: {
        action: "page6",
        label: "global.nav.page6",
      },
      4: {
        action: "page9",
        label: "global.nav.page9",
      },
    };
    if (window.column === 2) delete menu2[0];
    return { context, menu: menu2, settings: { type: "button", parent: null, children: [], isParentNav: true } };
  };

  init2 = (context) => {
    const objectDevice = context.objectDevice;
    const menuDevices = {};
    for (let index in objectDevice) {
      const item = context.objectDevice[index];
      menuDevices[index] = {
        action: "changeDevices",
        value: index,
        label: item.deviceName,
      };
    }
    return { context, menu: menuDevices, settings: { type: "button", parent: null, children: [], isParentNav: true } };
  };

  init3 = (context) => {
    const menu5 = {};
    for (let index in context.objectLanguages) {
      const lang = context.objectLanguages[index];
      if (lang.adminPanel === 0) continue;
      menu5[index] = {
        action: "changeLanguage",
        label: lang.languageName,
        value: index,
        backgroundImage: `flag-${lang.flag}`,
      };
    }
    return { context, menu: menu5, settings: { type: "button", parent: null, children: [], isParentNav: true } };
  };
}
