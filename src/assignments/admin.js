/*
  Requirement: Make the "Manage Assignments" page interactive.

  Instructions:
  1. Link this file to `admin.html` using:
     <script src="admin.js" defer></script>
  
  2. In `admin.html`, add an `id="assignments-tbody"` to the <tbody> element
     so you can select it.
  
  3. Implement the TODOs below.
*/
// PHP URL
const ASSIGNMENT_URL = `./api/index.php?resource=assignments`;

// --- Global Data Store ---
let assignments = [];

// --- Element Selections ---
// TODO: Select the assignment form ('#assignment-form').
// TODO: Select the assignments table body ('#assignments-tbody').
const form = document.querySelector('#assignment-form');
const table = document.querySelector('#assignments-tbody');

// Some UI elements used for edit state 
const submitBtn = document.getElementById('add-assignment'); 
const cancelBtn = document.getElementById('cancel-edit-button');
const formTitle = document.getElementById('form-title');

const searchInput = document.getElementById("Search-input");
const filterSelect = document.getElementById("filter-select");
const orderBtn = document.getElementById("order-btn");
let sortAsc = true;
let timer;

// ensure required DOM exists
if (!form) console.log('Form element #assignment-form not found in DOM');
if (!table) console.log('Table body #assignments-tbody not found in DOM');

// --- Helpers ---
function safeJson(res) {
  return res.text().then(text => {
    try {
      return JSON.parse(text);
    } catch (err) {
      // make the error clearer and include server response
      const e = new Error('Invalid JSON response from API');
      e.serverResponse = text;
      throw e;
    }
  });
}

// --- Functions ---

/**
 * TODO: Implement the createAssignmentRow function.
 * It takes one assignment object {id, title, dueDate}.
 * It should return a <tr> element with the following <td>s:
 * 1. A <td> for the `title`.
 * 2. A <td> for the `dueDate`.
 * 3. A <td> containing two buttons:
 * - An "Edit" button with class "edit-btn" and `data-id="${id}"`.
 * - A "Delete" button with class "delete-btn" and `data-id="${id}"`.
 */
function createAssignmentRow(assignment) {
  const tr = document.createElement('tr');

  const titleTd = document.createElement('td');
  titleTd.textContent = assignment.title ?? '';
  tr.appendChild(titleTd);

  const dueDateTd = document.createElement('td');
  // match DB/ PHP field name: due_date
  dueDateTd.textContent = assignment.due_date ?? '';
  tr.appendChild(dueDateTd);

  const actionsTd = document.createElement('td');
  actionsTd.classList.add('action-td');

  const editBtn = document.createElement('button');
  editBtn.className = 'edit-btn';
  editBtn.dataset.id = assignment.id;
  editBtn.textContent = 'Edit';

  const deleteBtn = document.createElement('button');
  deleteBtn.className = 'delete-btn';
  deleteBtn.dataset.id = assignment.id;
  deleteBtn.textContent = 'Delete';

  actionsTd.appendChild(editBtn);
  actionsTd.appendChild(deleteBtn);
  tr.appendChild(actionsTd);

  return tr;
}

/**
 * TODO: Implement the renderTable function.
 * It should:
 * 1. Clear the `assignmentsTableBody`.
 * 2. Loop through the global `assignments` array.
 * 3. For each assignment, call `createAssignmentRow()`, and
 * append the resulting <tr> to `assignmentsTableBody`.
 */
function renderTable() {
  if (!table) return;
  table.innerHTML = '';
  assignments.forEach(a => table.appendChild(createAssignmentRow(a)));
}

// reset edit state helper
function resetEdit() {
  if (!form) return;
  form.reset();
  delete form.dataset.editId;
  if (submitBtn) submitBtn.textContent = 'Add Assignment';
  if (cancelBtn) cancelBtn.style.display = 'none';
  if (formTitle) formTitle.textContent = 'Add a New Assignment';
}

/**
 * TODO: Implement the handleAddAssignment function.
 * This is the event handler for the form's 'submit' event.
 * It should:
 * 1. Prevent the form's default submission.
 * 2. Get the values from the title, description, due date, and files inputs.
 * 3. Create a new assignment object with a unique ID (e.g., `id: \`asg_${Date.now()}\``).
 * 4. Add this new assignment object to the global `assignments` array (in-memory only).
 * 5. Call `renderTable()` to refresh the list.
 * 6. Reset the form.
 */
function handleAddAssignment(event) {
  event.preventDefault();
  if (!form) return;

  const title = document.querySelector('#assignment-title')?.value ?? '';
  const description = document.querySelector('#assignment-description')?.value ?? '';
  const due_date = document.querySelector('#assignment-due-date')?.value ?? '';
  const filesRaw = document.querySelector('#assignment-files')?.value ?? '';
  const files = filesRaw.split('\n').map(s => s.trim()).filter(s => s);

  if (!form.dataset.editId) {
    // Create
    const newAssignment = {
      id: '',
      title,
      description,
      due_date,   // IMPORTANT: use due_date to match DB/PHP
      files
    };

    fetch(ASSIGNMENT_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(newAssignment)
    })
    .then(safeJson)
    .then(result => {
      if (!result || result.success !== true) {
        throw new Error(result?.error || 'Could not add assignment');
      }
      newAssignment.id = result.data;
      assignments.push(newAssignment);
      renderTable();
      resetEdit();
      table?.scrollIntoView({ behavior: 'smooth' });
    })
    .catch(err => {
      console.error('Error adding assignment:', err);
      if (err.serverResponse) {
        console.error('Server response (not JSON):\n', err.serverResponse);
      }
    });

  } else {
    // Update
    const id = form.dataset.editId;
    const updated = { id, title, description, due_date, files };

    fetch(`${ASSIGNMENT_URL}&id=${encodeURIComponent(id)}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(updated)
    })
    .then(safeJson)
    .then(result => {
      if (!result || result.success !== true) {
        throw new Error(result?.error || 'Could not update assignment');
      }
      const idx = assignments.findIndex(a => String(a.id) === String(id));
      if (idx > -1) {
        assignments[idx] = Object.assign({}, assignments[idx], updated);
      }
      renderTable();
      resetEdit();
      table?.scrollIntoView({ behavior: 'smooth' });
    })
    .catch(err => {
      console.error('Error updating assignment:', err);
    });
  }
}

/**
 * TODO: Implement the handleTableClick function.
 * This is an event listener on the `assignmentsTableBody` (for delegation).
 * It should:
 * 1. Check if the clicked element (`event.target`) has the class "delete-btn".
 * 2. If it does, get the `data-id` attribute from the button.
 * 3. Update the global `assignments` array by filtering out the assignment
 * with the matching ID (in-memory only).
 * 4. Call `renderTable()` to refresh the list.
 */
function handleTableClick(event) {
  const t = event.target;
  if (!t) return;

  if (t.classList.contains('delete-btn')) {
    const id = t.dataset.id;
    if (!confirm('Delete this assignment?')) return;

    fetch(`${ASSIGNMENT_URL}&id=${encodeURIComponent(id)}`, { method: 'DELETE' })
      .then(safeJson)
      .then(result => {
        if (!result || result.success !== true) {
          throw new Error(result?.error || 'Could not delete');
        }
        assignments = assignments.filter(a => String(a.id) !== String(id));
        renderTable();
      })
      .catch(err => {
        console.error('Error deleting assignment:', err);
      });

  } else if (t.classList.contains('edit-btn')) {
    const id = t.dataset.id;
    const assignment = assignments.find(a => String(a.id) === String(id));

    form.dataset.editId = id;
    document.querySelector('#assignment-title').value = assignment.title || '';
    document.querySelector('#assignment-description').value = assignment.description || '';
    document.querySelector('#assignment-due-date').value = assignment.due_date || '';
    document.querySelector('#assignment-files').value = (assignment.files || []).join('\n');

    form.scrollIntoView({ behavior: 'smooth' });
    if (submitBtn) submitBtn.textContent = 'Update Assignment';
    if (cancelBtn) cancelBtn.style.display = 'inline-block';
    if (formTitle) formTitle.textContent = 'Update Assignment';
  }
}

/* for filtering */
function loadFilteredAssignments(){
  const search = searchInput.value.trim();
  const sort = filterSelect.value;
  const order = sortAsc ? "asc" : "desc";

  const url = `${ASSIGNMENT_URL}&search=${encodeURIComponent(search)}&sort=${sort}&order=${order}`;

  fetch(url).then(response=>response.json()).then(result=>{
     assignments = result.data;
     renderTable();
  }).catch(error => console.error("Error fetching filtered assignments:", error));
}

//Event listeners for search & filter
searchInput.addEventListener("input", (e)=>{
  clearTimeout(timer);
  timer = setTimeout(() => loadFilteredAssignments(), 300);
});
filterSelect.addEventListener("change", loadFilteredAssignments);
orderBtn.addEventListener("click", () => {
  sortAsc = !sortAsc;
  orderBtn.textContent = sortAsc ? "Asc" : "Desc";
  loadFilteredAssignments();
});

/**
 * TODO: Implement the loadAndInitialize function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use `fetch()` to get data from 'assignments.json'.
 * 2. Parse the JSON response and store the result in the global `assignments` array.
 * 3. Call `renderTable()` to populate the table for the first time.
 * 4. Add the 'submit' event listener to `assignmentForm` (calls `handleAddAssignment`).
 * 5. Add the 'click' event listener to `assignmentsTableBody` (calls `handleTableClick`).
 */
async function loadAndInitialize() {
  if (!form || !table) {
    console.error('Missing required DOM elements. Make sure #assignment-form and #assignments-tbody exist in the HTML.');
    return;
  }

  try {
    const response = await fetch(ASSIGNMENT_URL);
    if (!response.ok) {
      const txt = await response.text();
      throw new Error(`Fetch failed: ${response.status} ${response.statusText}\n${txt}`);
    }
    const result = await response.json().catch(err => {
      // if JSON parse fails, include raw text
      const e = new Error('Invalid JSON from server');
      e.serverText = null;
      throw e;
    });

    if (!result || result.success !== true) {
      throw new Error(result?.error || 'API returned error');
    }

    // PHP returns array in result.data
    assignments = Array.isArray(result.data) ? result.data : [];
    renderTable();

    // wire events
    form.addEventListener('submit', handleAddAssignment);
    table.addEventListener('click', handleTableClick);

    // optional cancel button handler
    if (cancelBtn) cancelBtn.addEventListener('click', resetEdit);

    console.info('Assignments loaded:', assignments.length);
  } catch (err) {
    console.error('Error loading assignments:', err);
    if (err.serverText) console.error(err.serverText);
    if (err.serverResponse) console.error(err.serverResponse);
  }
}

loadAndInitialize();
