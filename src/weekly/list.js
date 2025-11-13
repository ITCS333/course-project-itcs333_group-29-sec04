/*
  Requirement: Populate the "Weekly Course Breakdown" list page.

  Instructions:
  1. Link this file to `list.html` using:
     <script src="list.js" defer></script>

  2. In `list.html`, add an `id="week-list-section"` to the
     <section> element that will contain the weekly articles.

  3. Implement the TODOs below.
*/

// --- Element Selections ---
// TODO: Select the section for the week list ('#week-list-section').
const listSection=document.getElementById("week-list-section");
// --- Functions ---

/**
 * TODO: Implement the createWeekArticle function.
 * It takes one week object {id, title, startDate, description}.
 * It should return an <article> element matching the structure in `list.html`.
 * - The "View Details & Discussion" link's `href` MUST be set to `details.html?id=${id}`.
 * (This is how the detail page will know which week to load).
 */
function createWeekArticle(week) {
  // ... your implementation here ...
  //Elements Creation
  const article=document.createElement('article');
  const weekTitle=document.createElement('h2');
  const startDate=document.createElement('p');
  const description=document.createElement('p');
  const link=document.createElement('a');
  //Value assignment
  weekTitle.textContent=week.title;
  startDate.textContent="Starts on: "+week.startDate;
  description.textContent=week.description;
  link.textContent="View Details & Discussion";
  link.href="details.html?id="+week.id;

  //Structure
  article.appendChild(weekTitle);
  article.appendChild(startDate);
  article.appendChild(description);
  article.appendChild(link);

  return article
}

/**
 * TODO: Implement the loadWeeks function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use `fetch()` to get data from 'weeks.json'.
 * 2. Parse the JSON response into an array.
 * 3. Clear any existing content from `listSection`.
 * 4. Loop through the weeks array. For each week:
 * - Call `createWeekArticle()`.
 * - Append the returned <article> element to `listSection`.
 */
async function loadWeeks() {
  // ... your implementation here ...
  try{
    const result= await fetch("api/weeks.json");
    const weekData= await result.json();
    
    listSection.innerHTML="";
    weekData.forEach(week=>{
      listSection.appendChild(createWeekArticle(week))
    });
  }catch(error){
    console.log("Error loading data:",error);
  }
}

// --- Initial Page Load ---
// Call the function to populate the page.
loadWeeks();
