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

  // A document picked and not saved yet: show a preview (picture) or a PDF icon with its name and size, and let the shop
  // take it back. Nothing is uploaded until the form is saved; the server checks type and size again.
  const MAX_BYTES=5*1024*1024;
  function formatSize(bytes){return bytes<1024*1024?Math.max(1,Math.round(bytes/1024))+" ك.ب":(bytes/1024/1024).toFixed(1)+" م.ب"}
  document.querySelectorAll("[data-upload-field]").forEach(function(field){
    const input=field.querySelector("input[type=file]"),panel=field.querySelector("[data-picked]"),image=field.querySelector("[data-picked-image]"),icon=field.querySelector("[data-picked-icon]"),name=field.querySelector("[data-picked-name]"),note=field.querySelector("[data-picked-note]"),clear=field.querySelector("[data-clear]"),hasSaved=field.querySelector("[data-has-saved]").dataset.hasSaved==="1";
    let previewUrl=null;
    function reset(){if(previewUrl){URL.revokeObjectURL(previewUrl);previewUrl=null}panel.hidden=true;panel.classList.remove("is-too-big");image.hidden=true;image.removeAttribute("src");icon.hidden=true}
    input.addEventListener("change",function(){
      reset();
      const file=input.files&&input.files[0];
      if(!file)return;
      name.textContent=file.name;
      const tooBig=file.size>MAX_BYTES;
      panel.classList.toggle("is-too-big",tooBig);
      note.textContent=tooBig?"الملف أكبر من 5 ميجابايت، اختر ملفًا أصغر.":formatSize(file.size)+(hasSaved?" — سيحلّ محل الملف المرفوع سابقًا عند الحفظ.":" — يُرفع عند حفظ التغييرات.");
      if(file.type.indexOf("image/")===0){previewUrl=URL.createObjectURL(file);image.src=previewUrl;image.hidden=false}else{icon.hidden=false}
      panel.hidden=false;
    });
    clear.addEventListener("click",function(){input.value="";reset();input.focus()});
  });

  function showToast(message,success){toast.querySelector("span").textContent=message;toast.classList.remove("is-warning");toast.classList.toggle("is-success",!!success);toast.classList.add("show");setTimeout(function(){toast.classList.remove("show")},4500)}
  /* The save was blocked: say which data is missing, by name, in a warning card. */
  var missingTimer;
  function fieldName(input){
    var wrap=input.closest(".upload-field"),label=wrap?wrap.querySelector(".upload-title"):input.closest("label");
    if(!label)return input.name||"";
    var copy=label.cloneNode(true);copy.querySelectorAll("em,input,select,textarea,small,span,i").forEach(function(node){node.remove()});
    return copy.textContent.split("*").join("").trim()||input.name||"";
  }
  function showMissingToast(invalid){
    var names=[];invalid.forEach(function(input){var name=fieldName(input);if(name&&names.indexOf(name)<0)names.push(name)});
    var holder=toast.querySelector("span");holder.textContent="";
    var title=document.createElement("strong");title.textContent="أكمل البيانات التالية قبل الحفظ ("+names.length+")";
    var list=document.createElement("ul");names.forEach(function(name){var item=document.createElement("li");item.textContent=name;list.appendChild(item)});
    holder.appendChild(title);holder.appendChild(list);
    toast.classList.remove("is-success");toast.classList.add("is-warning","show");
    clearTimeout(missingTimer);missingTimer=setTimeout(function(){toast.classList.remove("show","is-warning")},7000);
  }
  /* A required field left empty or wrong: a red border and a short message right under that field (no browser bubble). */
  function fieldHolder(input){return input.closest(".input-icon")||input}
  function clearFieldError(input){var holder=fieldHolder(input),note=holder.nextElementSibling;if(note&&note.classList.contains("field-error"))note.remove();input.classList.remove("is-invalid")}
  function showFieldError(input){
    clearFieldError(input);
    var holder=fieldHolder(input),note=document.createElement("small");
    note.className="field-error";note.setAttribute("role","alert");
    note.textContent=input.validity.valueMissing?"هذا الحقل مطلوب":(input.validity.typeMismatch||input.validity.patternMismatch?"القيمة المدخلة غير صالحة":input.validationMessage);
    holder.insertAdjacentElement("afterend",note);input.classList.add("is-invalid");
  }
  form.addEventListener("invalid",function(event){event.preventDefault();showFieldError(event.target)},true);
  form.addEventListener("input",function(event){if(event.target.matches("input,textarea,select"))clearFieldError(event.target)});
  form.addEventListener("submit",function(event){
    form.classList.add("was-validated");
    form.querySelectorAll(".field-error").forEach(function(note){note.remove()});
    if(!form.checkValidity()){event.preventDefault();var invalid=form.querySelectorAll(":invalid"),first=invalid[0];showMissingToast(invalid);if(first){first.scrollIntoView({block:"center",behavior:"smooth"});if(first.type!=="file")first.focus({preventScroll:true})}return}
    syncDays()
  });
  if(window.printProviderProfileFlash==="updated")showToast("تم حفظ التغييرات بنجاح",true);
  if(window.printProviderProfileFlash==="submitted")showToast("تم حفظ بياناتك وإرسال طلب الاعتماد للإدارة",true);
});
