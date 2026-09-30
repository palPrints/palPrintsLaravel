"use strict";
document.addEventListener("DOMContentLoaded",function(){
  const form=document.getElementById("printerProfileForm"),toast=document.getElementById("toast");
  if(!form)return;

  const view=document.getElementById("providerProfileView"),edit=document.getElementById("providerProfileEdit"),actions=document.getElementById("providerFormActions"),editButton=document.getElementById("editProviderProfile"),description=document.getElementById("providerPageDescription");
  function setEditing(isEditing){
    view.hidden=isEditing;edit.hidden=!isEditing;actions.hidden=!isEditing;
    if(editButton){editButton.hidden=isEditing;editButton.setAttribute("aria-expanded",String(isEditing))}
    if(description)description.textContent=isEditing?description.dataset.editText:description.dataset.viewText;
    if(isEditing){const first=edit.querySelector("input,textarea");if(first)first.focus({preventScroll:true});window.scrollTo({top:0,behavior:"smooth"})}
  }
  if(editButton)editButton.addEventListener("click",function(){setEditing(true)});

  const dayInputs=document.getElementById("workDaysInputs");
  function syncDays(){dayInputs.innerHTML="";document.querySelectorAll("#workDays button.selected").forEach(function(day){const input=document.createElement("input");input.type="hidden";input.name="days[]";input.value=day.dataset.day;dayInputs.appendChild(input)})}
  document.querySelectorAll("#workDays button").forEach(function(day){day.addEventListener("click",function(){day.classList.toggle("selected");syncDays()})});
  const availability=document.getElementById("availabilityToggle"),availabilityText=document.getElementById("availabilityText");
  availability.addEventListener("change",function(){availabilityText.innerHTML=availability.checked?'متاح <i></i>':'غير متاح';availabilityText.classList.toggle("off",!availability.checked)});

  function showToast(message){toast.querySelector("span").textContent=message;toast.classList.add("show");setTimeout(function(){toast.classList.remove("show")},2800)}
  form.addEventListener("submit",function(event){if(!form.reportValidity()){event.preventDefault();return}syncDays()});
  if(window.printProviderProfileFlash==="updated")showToast("تم حفظ التغييرات بنجاح");
});
