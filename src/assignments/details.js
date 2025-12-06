/*
  Requirement: Populate the assignment detail page and discussion forum.

  Instructions:
  1. Link this file to `details.html` using:
     <script src="details.js" defer></script>

  2. In `details.html`, add the following IDs:
     - To the <h1>: `id="assignment-title"`
     - To the "Due" <p>: `id="assignment-due-date"`
     - To the "Description" <p>: `id="assignment-description"`
     - To the "Attached Files" <ul>: `id="assignment-files-list"`
     - To the <div> for comments: `id="comment-list"`
     - To the "Add a Comment" <form>: `id="comment-form"`
     - To the <textarea>: `id="new-comment-text"`

  3. Implement the TODOs below.
*/

// --- Global Data Store ---
// These will hold the data related to *this* assignment.
let currentAssignmentId = null;
let currentComments = [];

const ASSIGNMENT_URL="./api/index.php?resource=assignments&id=";
const COMMENTS_URL="./api/index.php?resource=comments";

// --- Element Selections ---
// TODO: Select all the elements you added IDs for in step 2.
const title = document.querySelector('#assignment-title');
const due = document.querySelector("#assignment-due-date");
const description = document.querySelector("#assignment-description");
const AttachedFiles = document.querySelector("#assignment-files-list");
const commentList = document.querySelector("#comment-list");
const commentForm = document.querySelector("#comment-form");
const commentText = document.querySelector("#new-comment-text");
// --- Functions ---

/**
 * TODO: Implement the getAssignmentIdFromURL function.
 * It should:
 * 1. Get the query string from `window.location.search`.
 * 2. Use the `URLSearchParams` object to get the value of the 'id' parameter.
 * 3. Return the id.
 */
function getAssignmentIdFromURL() {
const string = window.location.search;
const params = new URLSearchParams(string);
const id = params.get("id");
return id;
}

/**
 * TODO: Implement the renderAssignmentDetails function.
 * It takes one assignment object.
 * It should:
 * 1. Set the `textContent` of `assignmentTitle` to the assignment's title.
 * 2. Set the `textContent` of `assignmentDueDate` to "Due: " + assignment's dueDate.
 * 3. Set the `textContent` of `assignmentDescription`.
 * 4. Clear `assignmentFilesList` and then create and append
 * `<li><a href="#">...</a></li>` for each file in the assignment's 'files' array.
 */
function renderAssignmentDetails(assignment) {

title.textContent = assignment.title;
due.textContent = `Due: ${assignment.due_date}`;
description.textContent = assignment.description;

 AttachedFiles.innerHTML = '';
 assignment.files.forEach(file =>{
  const li = document.createElement('li');
  const a = document.createElement('a');
  a.href = file;
  a.textContent = file;
  li.appendChild(a);
  AttachedFiles.appendChild(li);

 });
}

/**
 * TODO: Implement the createCommentArticle function.
 * It takes one comment object {author, text}.
 * It should return an <article> element matching the structure in `details.html`.
 */
function createCommentArticle(comment) {
  const article = document.createElement('article');
  const p = document.createElement('p');
  const footer = document.createElement('footer');
  p.textContent = comment.text;
  footer.textContent = `Posted by: ${comment.author}`;

  const deleteBtn = document.createElement('button');
  deleteBtn.textContent = "×";
  deleteBtn.dataset.id = comment.id;
  deleteBtn.classList.add("comment-delete-btn");
  article.classList.add("comment-item");
  
  article.appendChild(p);
  article.appendChild(footer);
  article.appendChild(deleteBtn);

  return article;
}

/**
 * TODO: Implement the renderComments function.
 * It should:
 * 1. Clear the `commentList`.
 * 2. Loop through the global `currentComments` array.
 * 3. For each comment, call `createCommentArticle()`, and
 * append the resulting <article> to `commentList`.
 */
function renderComments() {
 commentList.innerHTML= '';

 currentComments.forEach(comment => {
   const com = createCommentArticle(comment);
   commentList.append(com);
 });
}

/**
 * TODO: Implement the handleAddComment function.
 * This is the event handler for the `commentForm` 'submit' event.
 * It should:
 * 1. Prevent the form's default submission.
 * 2. Get the text from `newCommentText.value`.
 * 3. If the text is empty, return.
 * 4. Create a new comment object: { author: 'Student', text: commentText }
 * (For this exercise, 'Student' is a fine hardcoded author).
 * 5. Add the new comment to the global `currentComments` array (in-memory only).
 * 6. Call `renderComments()` to refresh the list.
 * 7. Clear the `newCommentText` textarea.
 */
async function handleAddComment(event) {
 event.preventDefault();

const text = commentText.value.trim();
if(!text){return;}

const newComment = {
  id:"",
  assignment_id:currentAssignmentId,
  author: 'Student', 
  text: text
};

fetch(COMMENTS_URL+"&assignment_id="+currentAssignmentId,{
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(newComment)
    })
    .then(response=>response.json())
    .then(result=>{
      if(result.success){
        newComment.id=result.data;
        //moved within fetch.then
        currentComments.push(newComment);
        renderComments();
        commentText.value="";
      }else{
        throw new Error("Could not fetch Comment")
      }
    }).catch(error=> console.log("Error:",error));
  }

function handleDeleteComment(event){
  if(event.target.classList.contains("comment-delete-btn")){
    const id=event.target.dataset.id;
    fetch(COMMENTS_URL+"&id="+id,{method:"DELETE"})
    .then(response=>response.json())
    .then(result=>{
      if(result.success){
        currentComments=currentComments.filter((comment)=> comment.id!=id);
        renderComments();
      }else{throw new Error(result.error || "Could not delete comment");}
    }).catch(error=> console.log("Error: ", error));
  }
}

/**
 * TODO: Implement an `initializePage` function.
 * This function needs to be 'async'.
 * It should:
 * 1. Get the `currentAssignmentId` by calling `getAssignmentIdFromURL()`.
 * 2. If no ID is found, display an error and stop.
 * 3. `fetch` both 'assignments.json' and 'comments.json' (you can use `Promise.all`).
 * 4. Find the correct assignment from the assignments array using the `currentAssignmentId`.
 * 5. Get the correct comments array from the comments object using the `currentAssignmentId`.
 * Store this in the global `currentComments` variable.
 * 6. If the assignment is found:
 * - Call `renderAssignmentDetails()` with the assignment object.
 * - Call `renderComments()` to show the initial comments.
 * - Add the 'submit' event listener to `commentForm` (calls `handleAddComment`).
 * 7. If the assignment is not found, display an error.
 */
async function initializePage() {
currentAssignmentId = getAssignmentIdFromURL();

if(!currentAssignmentId){
title.textContent = 'Assignment Not Found';
return;
}

const [assignmentLi, commentsLi] = await Promise.all([
fetch(ASSIGNMENT_URL+currentAssignmentId).then(result=> result.json()),
    fetch(COMMENTS_URL+"&assignment_id="+currentAssignmentId).then(result=>result.json())
]);

const assign = assignmentLi.data;

if(!assign){
  assignmentTitle.textContent = "Assignment Not Found";
  return;
}

 currentComments = commentsLi.data || [];
    renderAssignmentDetails(assign);
    renderComments();
    const commentForm = document.getElementById("comment-form");
    commentForm.addEventListener("submit", handleAddComment);
    commentList.addEventListener("click",handleDeleteComment);

} 

// --- Initial Page Load ---
initializePage();
