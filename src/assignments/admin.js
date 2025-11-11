/*
  Requirement: Make the "Manage Assignments" page interactive.

  Instructions:
  1. Link this file to `admin.html` using:
     <script src="admin.js" defer></script>
  
  2. In `admin.html`, add an `id="assignments-tbody"` to the <tbody> element
     so you can select it.
  
  3. Implement the TODOs below.
*/

// --- Global Data Store ---
// This will hold the assignments loaded from the JSON file.
let assignments = [];

// --- Element Selections ---
// TODO: Select the assignment form ('#assignment-form').
const form = document.querySelector('#assignment-form');
// TODO: Select the assignments table body ('#assignments-tbody').
const table = document.querySelector('#assignments-tbody');
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
  const Title = document.createElement('td');
  Title.textContent = assignment.title;
  tr.appendChild(Title);
  const dueDate = document.createElement('td');
  dueDate.textContent = assignment.dueDate;
  tr.appendChild(dueDate);

  const actions = document.createElement('td');
  const editBtn = document.createElement('button');
  editBtn.className = 'edit-btn';
  editBtn.dataset.id = assignment.id;
  editBtn.textContent = 'Edit';
  actions.appendChild(editBtn);
  const deleteBtn = document.createElement('button');
  deleteBtn.className = 'delete-btn';
  deleteBtn.dataset.id = assignment.id;
  deleteBtn.textContent = 'Delete';
  actions.appendChild(deleteBtn);

  tr.appendChild(actions);

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
table.innerHTML='';

assignments.forEach((assignment) => {
 const tr = createAssignmentRow(assignment);
 table.appendChild(tr);
});

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

 const title = document.querySelector('#assignment-title').value;
 const description = document.querySelector('#assignment-description').value;
 const dueDate = document.querySelector('#assignment-due-date').value;
 const files = document.querySelector('#assignment-files').value;

 const newAssignment = {
  id: `asg_${Date.now()}`,
  title,
  description,
  dueDate,
  files,
 };

 assignments.push(newAssignment);

 renderTable();

 event.target.reset();

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
const target = event.target;

if(target.classList.contains('delete-btn')){
  const assignmentId = target.dataset.id;
  assignments = assignments.filter(assignment => assignment.id !== assignmentId);
  return renderTable();
 }
}

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
try{
 const response = await fetch('/src/assignments/api/assignments.json');
 assignments = await response.json();
 renderTable();

 form.addEventListener('submit', handleAddAssignment);
 table.addEventListener('click', handleTableClick);

}catch(error){
  console.error('Failed to load assignments', error);
}
}

// --- Initial Page Load ---
// Call the main async function to start the application.
loadAndInitialize();
