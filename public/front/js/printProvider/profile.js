"use strict";
document.addEventListener("DOMContentLoaded",function(){
  const body=document.body,menuButton=document.getElementById("menuButton"),backdrop=document.getElementById("backdrop"),mobileLayout=window.matchMedia("(max-width:920px)");
  function syncMenuState(){const open=mobileLayout.matches?body.classList.contains("sidebar-open"):!body.classList.contains("sidebar-collapsed");menuButton.setAttribute("aria-expanded",String(open));menuButton.setAttribute("aria-label",open?"إغلاق القائمة":"فتح القائمة");menuButton.querySelector("i").className=open?"bi bi-x-lg":"bi bi-list"}
  function closeMenu(){body.classList.remove("sidebar-open");body.classList.add("sidebar-collapsed");syncMenuState()}
  menuButton.addEventListener("click",function(){if(mobileLayout.matches){body.classList.toggle("sidebar-open")}else{body.classList.toggle("sidebar-collapsed")}syncMenuState()});
  backdrop.addEventListener("click",closeMenu);
  mobileLayout.addEventListener("change",function(){body.classList.remove("sidebar-open");syncMenuState()});
  syncMenuState();

  const profileButton=document.getElementById("profileButton"),profileMenu=document.getElementById("profileMenu"),notificationButton=document.getElementById("bellButton"),notificationMenu=document.getElementById("notificationMenu"),notificationDot=notificationButton.querySelector(".dot");
  function closeProfileMenu(){profileMenu.classList.remove("open");profileMenu.hidden=true;profileButton.setAttribute("aria-expanded","false")}
  function closeNotificationMenu(){notificationMenu.classList.remove("open");notificationMenu.hidden=true;notificationButton.setAttribute("aria-expanded","false")}
  function shakeNotificationBell(){notificationButton.classList.remove("is-shaking");void notificationButton.offsetWidth;notificationButton.classList.add("is-shaking");setTimeout(function(){notificationButton.classList.remove("is-shaking")},600)}
  function pushNotification(message){notificationMenu.querySelector("p").textContent=message;notificationDot.hidden=false;shakeNotificationBell()}
  window.PalPrintingProfileNotification=pushNotification;
  window.addEventListener("palprints:notification",function(event){pushNotification(event.detail&&event.detail.message?event.detail.message:"لديك إشعار جديد")});
  profileButton.addEventListener("click",function(event){event.stopPropagation();const open=!profileMenu.classList.contains("open");closeNotificationMenu();profileMenu.hidden=!open;profileMenu.classList.toggle("open",open);profileButton.setAttribute("aria-expanded",String(open))});
  notificationButton.addEventListener("click",function(event){event.stopPropagation();const open=!notificationMenu.classList.contains("open");closeProfileMenu();notificationMenu.hidden=!open;notificationMenu.classList.toggle("open",open);notificationButton.setAttribute("aria-expanded",String(open));if(open)notificationDot.hidden=true});
  document.addEventListener("click",function(event){if(profileMenu.contains(event.target)||notificationMenu.contains(event.target))return;closeProfileMenu();closeNotificationMenu()});
  document.addEventListener("keydown",function(event){if(event.key==="Escape"){closeProfileMenu();closeNotificationMenu();if(mobileLayout.matches)closeMenu()}});

  const dayInputs=document.getElementById("workDaysInputs");
  function syncDays(){dayInputs.innerHTML="";document.querySelectorAll("#workDays button.selected").forEach(function(day){const input=document.createElement("input");input.type="hidden";input.name="days[]";input.value=day.dataset.day;dayInputs.appendChild(input)})}
  document.querySelectorAll("#workDays button").forEach(function(day){day.addEventListener("click",function(){day.classList.toggle("selected");syncDays()})});
  const availability=document.getElementById("availabilityToggle"),availabilityText=document.getElementById("availabilityText");
  availability.addEventListener("change",function(){availabilityText.innerHTML=availability.checked?'متاح <i></i>':'غير متاح';availabilityText.classList.toggle("off",!availability.checked)});

  const form=document.getElementById("printerProfileForm"),toast=document.getElementById("toast");
  function showToast(message){toast.querySelector("span").textContent=message;toast.classList.add("show");setTimeout(function(){toast.classList.remove("show")},2800)}
  form.addEventListener("submit",function(event){if(!form.reportValidity()){event.preventDefault();return}syncDays()});
  if(window.printProviderProfileFlash==="updated"){showToast("تم حفظ التغييرات بنجاح");pushNotification("تم حفظ التغييرات بنجاح")}

  const revealItems=Array.from(document.querySelectorAll(".reveal"));
  revealItems.forEach(function(item,index){item.style.setProperty("--reveal-delay",String(Math.min(index,3)*90)+"ms")});
  if(matchMedia("(prefers-reduced-motion:reduce)").matches||!("IntersectionObserver" in window)){revealItems.forEach(function(item){item.classList.add("visible")})}else{
    const revealObserver=new IntersectionObserver(function(entries){entries.forEach(function(entry){entry.target.classList.toggle("visible",entry.isIntersecting)})},{threshold:.05,rootMargin:"0px 0px -8% 0px"});
    revealItems.forEach(function(item){revealObserver.observe(item)});
  }
});
