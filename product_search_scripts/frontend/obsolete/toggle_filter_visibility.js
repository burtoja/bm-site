/**
 * Enables the toggling functionality of the items within
 * the main product category menu
 *
 * @param el
 */
function toggleVisibility(el) {
    const content = el.nextElementSibling;
    if (!content) return;
    if (content.style.display === "none") {
        content.style.display = "block";
        el.innerHTML = el.innerHTML.replace("[+]", "[-]");
    } else {
        content.style.display = "none";
        el.innerHTML = el.innerHTML.replace("[-]", "[+]");
    }
}