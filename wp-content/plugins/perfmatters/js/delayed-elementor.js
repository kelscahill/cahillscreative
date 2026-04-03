var pmeDeviceMode, pmeAnimationSettingsKeys, pmeCurrentAnimation;
function pmeAnimation() {
    ((pmeDeviceMode = document.createElement("span")).id = "elementor-device-mode"), pmeDeviceMode.setAttribute("class", "elementor-screen-only"), document.body.appendChild(pmeDeviceMode), requestAnimationFrame(pmeDetectAnimations);
}
function pmeDetectAnimations() {
    (pmeAnimationSettingsKeys = pmeListAnimationSettingsKeys(getComputedStyle(pmeDeviceMode, ":after").content.replace(/"/g, ""))),
        document.querySelectorAll(".elementor-invisible[data-settings]").forEach((a) => {
            let b = a.getBoundingClientRect();
            if (b.bottom >= 0 && b.top <= window.innerHeight)
                try {
                    pmeAnimateElement(a);
                } catch (c) {}
        });
}
function pmeAnimateElement(a) {
    let b = JSON.parse(a.dataset.settings),
        d = b._animation_delay || b.animation_delay || 0,
        c = b[pmeAnimationSettingsKeys.find((a) => b[a])];
    if ("none" === c) return void a.classList.remove("elementor-invisible");
    a.classList.remove(c), pmeCurrentAnimation && a.classList.remove(pmeCurrentAnimation), (pmeCurrentAnimation = c);
    let e = setTimeout(() => {
        a.classList.remove("elementor-invisible"), a.classList.add("animated", c), pmeRemoveAnimationSettings(a, b);
    }, d);
    window.addEventListener("perfmatters-startLoading", function () {
        clearTimeout(e);
    });
}
function pmeListAnimationSettingsKeys(b = "mobile") {
    let a = [""];
    switch (b) {
        case "mobile":
            a.unshift("_mobile");
        case "tablet":
            a.unshift("_tablet");
        case "desktop":
            a.unshift("_desktop");
    }
    let c = [];
    return (
        ["animation", "_animation"].forEach((b) => {
            a.forEach((a) => {
                c.push(b + a);
            });
        }),
        c
    );
}
function pmeRemoveAnimationSettings(a, b) {
    pmeListAnimationSettingsKeys().forEach((a) => delete b[a]), (a.dataset.settings = JSON.stringify(b));
}
document.addEventListener("DOMContentLoaded", pmeAnimation);
