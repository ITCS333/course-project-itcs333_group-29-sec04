/*
  Requirement: Make the "Manage Resources" page interactive.

  Instructions:
  1. Link this file to `admin.html` using:
     <script src="admin.js" defer></script>
  
  2. In `admin.html`, add an `id="resources-tbody"` to the <tbody> element
     inside your `resources-table`.
  
  3. Implement the TODOs below.
*/

// --- Global Data Store ---
// This will hold the resources loaded from the JSON file.
let resources = [];

// --- Element Selections ---
// TODO: Select the resource form ('#resource-form').
const resourceForm = document.querySelector("#resource-form");
// TODO: Select the resources table body ('#resources-tbody').
const resourcesTableBody = document.querySelector("#resources-tbody");
const RESOURCE_URL = "./api/index.php?resource=resources";

const searchInput = document.getElementById("Search-input");
const filterSelect = document.getElementById("filter-select");
const orderBtn = document.getElementById("order-btn");
let sortAsc = true;
let timer;
const submit_btn = document.getElementById("add-resource");
const cancel_btn = document.getElementById("cancel-edit-button");
const formTitle = document.getElementById("form-title");

// --- Functions ---

/**
 * TODO: Implement the createResourceRow function.
 * It takes one resource object {id, title, description}.
 * It should return a <tr> element with the following <td>s:
 * 1. A <td> for the `title`.
 * 2. A <td> for the `description`.
 * 3. A <td> containing two buttons:
 * - An "Edit" button with class "edit-btn" and `data-id="${id}"`.
 * - A "Delete" button with class "delete-btn" and `data-id="${id}"`.
 */
function createResourceRow(resource) {
  // ... your implementation here ...
  const row = document.createElement("tr");
  const title = document.createElement("td");
  title.textContent = resource.title;
  row.appendChild(title);

  const description = document.createElement("td");
  description.textContent = resource.description;
  row.appendChild(description);

  const buttonstd = document.createElement("td");
  buttonstd.classList.add("action-td");

  const b1 = document.createElement("button");
  b1.textContent = "Edit";
  b1.classList.add("edit-btn");
  b1.dataset.id = resource.id;
  buttonstd.appendChild(b1);

  const b2 = document.createElement("button");
  b2.textContent = "Delete";
  b2.classList.add("delete-btn");
  b2.dataset.id = resource.id;
  buttonstd.appendChild(b2);

  row.appendChild(buttonstd);
  return row;
}

/**
 * TODO: Implement the renderTable function.
 * It should:
 * 1. Clear the `resourcesTableBody`.
 * 2. Loop through the global `resources` array.
 * 3. For each resource, call `createResourceRow()`, and
 * append the resulting <tr> to `resourcesTableBody`.
 */
function renderTable() {
  // ... your implementation here ...
  resourcesTableBody.innerHTML = "";
  resources.forEach(res => {
    const r = createResourceRow(res);
    resourcesTableBody.appendChild(r);
  });
}

/**
 * TODO: Implement the handleAddResource function.
 * This is the event handler for the form's 'submit' event.
 * It should:
 * 1. Prevent the form's default submission.
 * 2. Get the values from the title, description, and link inputs.
 * 3. Create a new resource object with a unique ID (e.g., `id: \`res_${Date.now()}\``).
 * 4. Add this new resource object to the global `resources` array (in-memory only).
 * 5. Call `renderTable()` to refresh the list.
 * 6. Reset the form.
 */
function handleAddResource(event) {
  // ... your implementation here ...
  event.preventDefault();
  const title = document.getElementById("resource-title").value;
  const description = document.getElementById("resource-description").value;
  const link = document.getElementById("resource-link").value;
  const edit = Number(resourceForm.dataset.editId);
  if (!edit) {
    let newResource = { id: "", title, description, link };
    fetch(RESOURCE_URL, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(newResource)
    })
      .then(res => res.json())
      .then(result => {
        if (result.success) {
          newResource.id = result.id
          resources.push(newResource);
          renderTable();
          resourceForm.reset();
          resourcesTableBody.scrollIntoView({ behavior: "smooth" });
        }
        else console.error("Insertion failed:", result.message);
      });
  }
  else {
    const resource = resources.find(r => Number(r.id) === edit);
    resource.title = title;
    resource.description = description;
    resource.link = link;

    fetch(`${RESOURCE_URL}&id=${edit}`, {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        id: edit,
        title,
        description,
        link
      })
    })
      .then(res => res.json())
      .then(data => {
        if (!data.success) console.error("Update failed:", data.message);
        else {
          renderTable();
          restEdit();
          resourceForm.reset();
        }
      });

    delete resourceForm.dataset.editId;
    resourceForm.querySelector("button[type='submit']").textContent = "Add Resource";
  }
}


/**
 * TODO: Implement the handleTableClick function.
 * This is an event listener on the `resourcesTableBody` (for delegation).
 * It should:
 * 1. Check if the clicked element (`event.target`) has the class "delete-btn".
 * 2. If it does, get the `data-id` attribute from the button.
 * 3. Update the global `resources` array by filtering out the resource
 * with the matching ID (in-memory only).
 * 4. Call `renderTable()` to refresh the list.
 */
function handleTableClick(event) {
  // ... your implementation here ...
  if (event.target.classList.contains("delete-btn")) {
    const data = Number(event.target.dataset.id);

    fetch(`${RESOURCE_URL}&id=${data}`, {
      method: "DELETE"
    })
      .then(res => res.json())
      .then(result => {
        if (result.success) {
          resources = resources.filter(d => Number(d.id) !== data);
          renderTable();
        }
        else { console.error("Delete failed:", result.message); }
      })

  }

  else if (event.target.classList.contains("edit-btn")) {
    const id = Number(event.target.dataset.id);
    const resource = resources.find(d => Number(d.id) === id);
    if (!resource) return;

    document.getElementById("resource-title").value = resource.title;
    document.getElementById("resource-description").value = resource.description;
    document.getElementById("resource-link").value = resource.link;

    resourceForm.dataset.editId = id;
    resourceForm.querySelector("button[type='submit']").textContent = "Update Resource";
    cancel_btn.style.display = "inline-block";
    formTitle.textContent = "Update Resource";
    resourceForm.scrollIntoView({ behavior: "smooth" });
  }
}


function loadFilteredResources() {
  const search = searchInput.value.trim();
  const sort = filterSelect.value;
  const order = sortAsc ? "asc" : "desc";

  const url = `${RESOURCE_URL}&search=${encodeURIComponent(search)}&sort=${sort}&order=${order}`;

  fetch(url).then(response => response.json()).then(result => {
    resources = result.data;
    renderTable();
  }).catch(error => console.error("Error fetching filtered resources:", error));
}
searchInput.addEventListener("input", e => {
  clearTimeout(timer);
  timer = setTimeout(() => loadFilteredResources(), 300);
});
filterSelect.addEventListener("change", loadFilteredResources);
orderBtn.addEventListener("click", () => {
  sortAsc = !sortAsc;
  orderBtn.textContent = sortAsc ? "Asc" : "Desc";
  loadFilteredResources();
});


cancel_btn.addEventListener("click", restEdit);
function restEdit() {
  resourceForm.reset();
  delete resourceForm.dataset.editId;
  submit_btn.textContent = "Add Resource";
  cancel_btn.style.display = "none";
  formTitle.textContent = "Add a New Resource";
}
/**
 * TODO: Implement the loadAndInitialize function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use `fetch()` to get data from 'resources.json'.
 * 2. Parse the JSON response and store the result in the global `resources` array.
 * 3. Call `renderTable()` to populate the table for the first time.
 * 4. Add the 'submit' event listener to `resourceForm` (calls `handleAddResource`).
 * 5. Add the 'click' event listener to `resourcesTableBody` (calls `handleTableClick`).
 */
async function loadAndInitialize() {
  // ... your implementation here ...
  const response = await fetch(RESOURCE_URL);
  const result = await response.json();
  resources = result.data || [];
  renderTable();
  resourceForm.addEventListener("submit", handleAddResource);
  resourcesTableBody.addEventListener("click", handleTableClick);
}

// --- Initial Page Load ---
// Call the main async function to start the application.
loadAndInitialize();
