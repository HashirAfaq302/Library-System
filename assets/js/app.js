async function suggest(inputEl, listEl, field, baseUrl) {
  const q = inputEl.value.trim();
  listEl.innerHTML = "";
  listEl.classList.add("d-none");
  if (q.length < 2) return;

  const mine = inputEl.dataset.suggestMine === "1" ? "&mine=1" : "";
  const url = `${baseUrl}/ajax/suggest.php?field=${encodeURIComponent(field)}&q=${encodeURIComponent(q)}${mine}`;
  const res = await fetch(url, { headers: { "Accept": "application/json" } });
  if (!res.ok) return;
  const data = await res.json();
  const items = Array.isArray(data.items) ? data.items : [];
  if (!items.length) return;

  for (const item of items) {
    const div = document.createElement("div");
    div.className = "typeahead-item";
    div.textContent = item;
    div.addEventListener("click", () => {
      inputEl.value = item;
      listEl.innerHTML = "";
      listEl.classList.add("d-none");
    });
    listEl.appendChild(div);
  }
  listEl.classList.remove("d-none");
}

document.addEventListener("DOMContentLoaded", () => {
  const baseUrl = document.documentElement.dataset.baseUrl || "";
  for (const el of document.querySelectorAll("[data-suggest-field]")) {
    const field = el.dataset.suggestField;
    const listId = el.dataset.suggestList;
    const listEl = document.getElementById(listId);
    if (!listEl) continue;
    el.addEventListener("input", () => suggest(el, listEl, field, baseUrl));
    el.addEventListener("blur", () => setTimeout(() => listEl.classList.add("d-none"), 150));
  }
});

