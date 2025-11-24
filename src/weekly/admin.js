/*
  Requirement: Make the "Manage Weekly Breakdown" page interactive.

  Instructions:
  1. Link this file to `admin.html` using:
     <script src="admin.js" defer></script>
  
  2. In `admin.html`, add an `id="weeks-tbody"` to the <tbody> element
     inside your `weeks-table`.
  
  3. Implement the TODOs below.
*/
//Added php URL
const WEEKS_URL = `./api/index.php?resource=weeks`;

// --- Global Data Store ---
// This will hold the weekly data loaded from the JSON file.
let weeks = [];
// --- Element Selections ---
// TODO: Select the week form ('#week-form').
const weekForm=document.getElementById('week-form');
// TODO: Select the weeks table body ('#weeks-tbody').
const weeksTable=document.getElementById('weeks-tbody');

//Added
//Required for update part:
const submit_btn=document.getElementById("add-week");
const cancel_btn=document.getElementById("cancel-edit-button");
const formTitle=document.getElementById("form-title");

//Required for search& filter
const searchInput = document.getElementById("Search-input");
const filterSelect = document.getElementById("filter-select");
const orderBtn = document.getElementById("order-btn");
let sortAsc = true;
let timer;


// --- Functions ---

/**
 * TODO: Implement the createWeekRow function.
 * It takes one week object {id, title, description}.
 * It should return a <tr> element with the following <td>s:
 * 1. A <td> for the `title`.
 * 2. A <td> for the `description`.
 * 3. A <td> containing two buttons:
 * - An "Edit" button with class "edit-btn" and `data-id="${id}"`.
 * - A "Delete" button with class "delete-btn" and `data-id="${id}"`.
 */
function createWeekRow(week) {
  // ... your implementation here ...
  //Elements creation
  const tableRow=document.createElement('tr');
  const title=document.createElement('td');
  const description=document.createElement('td');
  const btnContainer=document.createElement('td');
  const Edit=document.createElement('button');
  const Delete=document.createElement('button');

  //Values Assignment
  title.textContent=week.title;
  description.textContent=week.description;
  Edit.textContent="Edit";
  Delete.textContent="Delete";

  //classes
  Edit.classList.add("edit-btn");
  Delete.classList.add("delete-btn");
  btnContainer.classList.add("action-td");

  //Datasets
  Edit.dataset.id=week.id;
  Delete.dataset.id=week.id;

  //Structure
  tableRow.appendChild(title);
  tableRow.appendChild(description);
  tableRow.appendChild(btnContainer);
  btnContainer.appendChild(Edit);
  btnContainer.appendChild(Delete);

  return tableRow;
}

/**
 * TODO: Implement the renderTable function.
 * It should:
 * 1. Clear the `weeksTableBody`.
 * 2. Loop through the global `weeks` array.
 * 3. For each week, call `createWeekRow()`, and
 * append the resulting <tr> to `weeksTableBody`.
 */
function renderTable() {
  // ... your implementation here ...
  weeksTable.innerHTML="";
  weeks.forEach(week=>{
    weeksTable.appendChild(createWeekRow(week));
  })
}

/**
 * TODO: Implement the handleAddWeek function.
 * This is the event handler for the form's 'submit' event.
 * It should:
 * 1. Prevent the form's default submission.
 * 2. Get the values from the title, start date, and description inputs.
 * 3. Get the value from the 'week-links' textarea. Split this value
 * by newlines (`\n`) to create an array of link strings.
 * 4. Create a new week object with a unique ID (e.g., `id: \`week_${Date.now()}\``).
 * 5. Add this new week object to the global `weeks` array (in-memory only).
 * 6. Call `renderTable()` to refresh the list.
 * 7. Reset the form.
 */

//Added Update feature within handelAddWeek
function handleAddWeek(event) {
  // ... your implementation here ...
  event.preventDefault();
  //Get Values
  const title=document.getElementById('week-title').value;
  const startDate=document.getElementById('week-start-date').value;
  const description=document.getElementById('week-description').value;
  let weekLinks=document.getElementById('week-links').value.split("\n");

  //Removing empty lines from links
   weekLinks = weekLinks.filter(link => link.trim() !== "");
   //Add Mode
   if(!weekForm.dataset.editId){
    let week={
      id:"",//fixed to match php
      title:title,
      start_date:startDate,//fixed to match php
      description:description,
      links:weekLinks
    };
    //Fetch part
      fetch(WEEKS_URL,
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(week)
        })
      .then(response=>response.json())
      .then(result=>{
        if(result.success){
          week.id=result.data;
          //Push to weeks array
          weeks.push(week);
          renderTable();//Refresh list
          weekForm.reset();//Reset form
          weeksTable.scrollIntoView({ behavior: "smooth" });
        }else{
          throw new Error("Could not fetch weeks");
        }
      }).catch(error=> console.log("Error: ",error));
  }else{
    //Update Mode
    const id=weekForm.dataset.editId;
    const week=weeks.find(week=>week.id==id);

     const updated_week={
      id:id,
      title:title,
      start_date:startDate,
      description:description,
      links:weekLinks
    };
    //Fetch Part
    fetch(WEEKS_URL+`&id=${id}`,
      {
        method:"PUT", 
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(updated_week)
      })
      .then(response=>response.json())
      .then(result=>{
        if(!result.success){
          throw new Error(result.error);
        }
        //Updating week within week array
        Object.assign(week, updated_week);

        renderTable();
        restEdit();
        weeksTable.scrollIntoView({ behavior: "smooth" });

     }).catch(err => console.log(err));
  }
}

//handeling cancel edit & form reset
cancel_btn.addEventListener("click", restEdit);
function restEdit(){
  weekForm.reset();
  delete weekForm.dataset.editId;  
  submit_btn.textContent = "Add Week"; 
  cancel_btn.style.display = "none";
  formTitle.textContent="Add a New Week";
}

/**
 * TODO: Implement the handleTableClick function.
 * This is an event listener on the `weeksTableBody` (for delegation).
 * It should:
 * 1. Check if the clicked element (`event.target`) has the class "delete-btn".
 * 2. If it does, get the `data-id` attribute from the button.
 * 3. Update the global `weeks` array by filtering out the week
 * with the matching ID (in-memory only).
 * 4. Call `renderTable()` to refresh the list.
 */


function handleTableClick(event) {
  // ... your implementation here ...
  if(event.target.classList.contains("delete-btn")){
    //Delete week
    const id=event.target.dataset.id;
    //Fetch part
    fetch(`${WEEKS_URL}&id=${id}`, {method:"DELETE"})
    .then(response=>response.json())
    .then(result=>{
      if(result.success){
        //Remove week from weeks array
        weeks=weeks.filter((week)=> week.id!=id);
        renderTable();
      }else{
        throw new Error(result.error || "Could not delete week");
      }
    }).catch(error=> console.log("Error: ", error))
    
  }else if(event.target.classList.contains("edit-btn")){
    //Edit Week:
    //Get the week id and week info 
    const id=event.target.dataset.id;
    const week=weeks.find(week=>week.id==id);
    weekForm.dataset.editId = id;

    //Show week info in the form
    const title=document.getElementById('week-title');
    const startDate=document.getElementById('week-start-date');
    const description=document.getElementById('week-description');
    const weekLinks=document.getElementById('week-links');
    title.value=week.title;
    startDate.value=week.start_date;
    description.value=week.description;
    weekLinks.value=week.links.join("\n");

    //Scroll to the form
    weekForm.scrollIntoView({ behavior: "smooth" });
    
    submit_btn.textContent = "Update Week";
    cancel_btn.style.display="inline-block";
    formTitle.textContent="Update week"; 
  }
}


// ------ Search & Filter ------

//Added a function for search & filter
function loadFilteredWeeks(){
  const search = searchInput.value.trim();
  const sort = filterSelect.value;
  const order = sortAsc ? "asc" : "desc";

  const url = `${WEEKS_URL}&search=${encodeURIComponent(search)}&sort=${sort}&order=${order}`;

  fetch(url).then(response=>response.json()).then(result=>{
     weeks = result.data;
     renderTable();
  }).catch(error => console.error("Error fetching filtered weeks:", error));
}

//Event listeners for search & filter
searchInput.addEventListener("input", (e)=>{
  clearTimeout(timer);
  timer = setTimeout(() => loadFilteredWeeks(), 300);
});
filterSelect.addEventListener("change", loadFilteredWeeks);
orderBtn.addEventListener("click", () => {
  sortAsc = !sortAsc;
  orderBtn.textContent = sortAsc ? "Asc" : "Desc";
  loadFilteredWeeks();
});





/**
 * TODO: Implement the loadAndInitialize function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use `fetch()` to get data from 'weeks.json'.
 * 2. Parse the JSON response and store the result in the global `weeks` array.
 * 3. Call `renderTable()` to populate the table for the first time.
 * 4. Add the 'submit' event listener to `weekForm` (calls `handleAddWeek`).
 * 5. Add the 'click' event listener to `weeksTableBody` (calls `handleTableClick`).
 */
async function loadAndInitialize() {
  // ... your implementation here ...
  try{
    //fixed to fetch from php
    const response= await fetch(WEEKS_URL);
    if (!response.ok) {
      throw new Error("Could not fetch weeks");
    }
    const result=await response.json();
    if (!result.success) {
      throw new Error(result.error || "API returned an error");
    }
    weeks= result.data;
    
    renderTable();
    weekForm.addEventListener('submit',handleAddWeek);
    weeksTable.addEventListener('click',handleTableClick); 
  }catch(error){
    console.log("Error loading data:",error);
  }
}

// --- Initial Page Load ---
// Call the main async function to start the application.
loadAndInitialize();
