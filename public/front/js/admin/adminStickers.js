(function () {
  'use strict';
  const config = window.PalPrintsAdminStickers;
  if (!config) return;
  const $ = id => document.getElementById(id);
  let categories = config.categories, settings = structuredClone(config.settings);
  let editingCategory = null, editingSticker = null, dirty = false, currentTab = 'library';
  let preview = null, page = 0, version = 0, timer, imageUrl;
  const esc = v => String(v ?? '').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const route = (name,id) => config.routes[name].replace('__ID__',id);
  const icon = (name,label,attr) => `<button type="button" title="${label}" aria-label="${label}" ${attr}><i class="bi bi-${name}"></i></button>`;
  const items = () => categories.flatMap(c=>c.stickers.map(s=>({...s,category_name:c.name})));
  function notice(message){if(window.PalAdmin?.toast)window.PalAdmin.toast(message);else alert(message);}
  async function request(url,data,method='POST') {
    const multi=data instanceof FormData;
    const response=await fetch(url,{method,headers:{Accept:'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,...(multi?{}:{'Content-Type':'application/json'})},body:multi?data:JSON.stringify(data)});
    const result=await response.json().catch(()=>({}));
    if(!response.ok||response.redirected){const error=new Error(Object.values(result.errors||{}).flat()[0]||result.message||'تعذر الحفظ. تحقق من الاتصال وتسجيل الدخول.');error.fields=result.errors||{};throw error;}
    return result;
  }
  function errors(form,error){
    form.querySelectorAll('.field-error').forEach(n=>n.remove());
    Object.entries(error.fields||{}).forEach(([name,messages])=>{const input=[...form.querySelectorAll('[name]')].find(n=>n.name===name);if(input){const text=document.createElement('small');text.className='field-error';text.textContent=messages[0];input.after(text);}});
  }
  function tab(name){
    currentTab=name;
    document.querySelectorAll('[data-admin-tab]').forEach(b=>{const active=b.dataset.adminTab===name;b.setAttribute('aria-selected',active);b.tabIndex=active?0:-1;});
    ['library','categories','pricing','printing'].forEach(key=>$('panel-'+key).hidden=key!==name);
    $('settingsForm').parentElement.hidden=!['pricing','printing'].includes(name);
    $('addCategoryButton').hidden=name!=='categories';$('addStickerButton').hidden=name!=='library';
    if(name==='printing')schedule();
  }
  document.querySelectorAll('[data-admin-tab]').forEach((button,index,buttons)=>{
    button.onclick=()=>tab(button.dataset.adminTab);
    button.onkeydown=e=>{if(!['ArrowLeft','ArrowRight','Home','End'].includes(e.key))return;e.preventDefault();const next=e.key==='Home'?0:e.key==='End'?buttons.length-1:(index+(e.key==='ArrowLeft'?1:-1)+buttons.length)%buttons.length;buttons[next].focus();buttons[next].click();};
  });
  function render(){
    const selected=$('categoryFilter').value||'all';
    $('categoryFilter').innerHTML='<option value="all">كل التصنيفات</option>'+categories.map(c=>`<option value="${c.id}">${esc(c.name)}</option>`).join('');
    $('categoryFilter').value=categories.some(c=>String(c.id)===selected)?selected:'all';
    $('stickerCategoryInput').innerHTML=categories.map(c=>`<option value="${c.id}">${esc(c.name)}</option>`).join('');
    $('categoriesBar').innerHTML=categories.map(c=>`<tr><td>${esc(c.name)}</td><td>${c.stickers.length}</td><td>${c.sort_order}</td><td>${c.is_active?'مفعل':'مخفي'}</td><td>${icon('pencil','تعديل',`data-category-edit="${c.id}"`)}${icon('trash','حذف',`data-category-delete="${c.id}"`)}</td></tr>`).join('')||'<tr><td colspan="5">لا توجد تصنيفات</td></tr>';
    grid();
  }
  function grid(){
    const filtered=items().filter(s=>($('categoryFilter').value==='all'||String(s.sticker_category_id)===$('categoryFilter').value)&&($('statusFilter').value==='all'||Number(s.is_active)===Number($('statusFilter').value))&&s.name.toLocaleLowerCase().includes($('stickerSearch').value.trim().toLocaleLowerCase()));
    $('stickerCount').textContent=filtered.length+' ملصق';
    $('stickersGrid').innerHTML=filtered.map(s=>`<article class="sticker-card"><div class="sticker-visual"><img src="${esc(s.image_url)}" alt="${esc(s.name)}" loading="lazy"></div><div class="sticker-info"><h3>${esc(s.name)}</h3><p>${esc(s.category_name)} · ${s.is_active?'مفعل':'مخفي'} · ${s.sort_order}</p></div><div class="sticker-actions">${icon('eye','معاينة',`data-preview="${s.id}"`)}${icon('pencil','تعديل',`data-edit="${s.id}"`)}${icon('trash','حذف',`data-delete="${s.id}"`)}</div></article>`).join('');
    $('stickersEmpty').hidden=filtered.length>0;
  }
  ['stickerSearch','categoryFilter','statusFilter'].forEach(id=>$(id).addEventListener('input',grid));
  function openCategory(item=null){editingCategory=item;$('categoryForm').reset();$('categoryError').hidden=true;$('categoryDialogTitle').textContent=item?'تعديل تصنيف':'إضافة تصنيف';$('categoryNameInput').value=item?.name||'';$('categoryOrderInput').value=item?.sort_order||0;$('categoryActiveInput').checked=item?item.is_active:true;$('categoryDialog').showModal();}
  function displayImage(url){$('stickerImagePreview').hidden=!url;if(url)$('stickerImagePreview').src=url;else $('stickerImagePreview').removeAttribute('src');}
  function openSticker(item=null){
    if(!categories.length)return openCategory();editingSticker=item;$('stickerForm').reset();$('stickerError').hidden=true;$('stickerDialogTitle').textContent=item?'تعديل ملصق':'إضافة ملصق';$('stickerNameInput').value=item?.name||'';$('stickerCategoryInput').value=item?.sticker_category_id||categories[0].id;$('stickerOrderInput').value=item?.sort_order||0;$('stickerActiveInput').checked=item?item.is_active:true;$('stickerImageInput').required=!item;displayImage(item?.image_url);$('stickerDialog').showModal();
  }
  $('stickerImageInput').onchange=()=>{if(imageUrl)URL.revokeObjectURL(imageUrl);imageUrl=$('stickerImageInput').files[0]?URL.createObjectURL($('stickerImageInput').files[0]):null;displayImage(imageUrl||editingSticker?.image_url);};
  $('addCategoryButton').onclick=()=>openCategory();$('addStickerButton').onclick=()=>openSticker();
  document.querySelectorAll('[data-dialog-close]').forEach(b=>b.onclick=()=>b.closest('dialog').close());
  Object.entries({categoryNameInput:'name',categoryOrderInput:'sort_order',categoryActiveInput:'is_active',stickerNameInput:'name',stickerCategoryInput:'sticker_category_id',stickerOrderInput:'sort_order',stickerActiveInput:'is_active',stickerImageInput:'image'}).forEach(([id,name])=>$(id).name=name);
  ['category','sticker'].forEach(kind=>{const form=$(kind+'Form');form.onsubmit=async event=>{
    event.preventDefault();const button=form.querySelector('[type="submit"]');if(button.disabled)return;
    const item=kind==='category'?editingCategory:editingSticker,data=new FormData(form);data.set('is_active',$(kind+'ActiveInput').checked?'1':'0');if(kind==='sticker'&&!$('stickerImageInput').files.length)data.delete('image');if(item)data.set('_method','PATCH');button.disabled=true;
    try{const key=kind==='category'?'category':'item';categories=(await request(item?route(key+'Update',item.id):config.routes[key+'Store'],data)).categories;$(kind+'Dialog').close();render();notice('تم الحفظ.');}
    catch(e){$(kind+'Error').textContent=e.message;$(kind+'Error').hidden=false;errors(form,e);}finally{button.disabled=false;}
  };});
  $('categoriesBar').onclick=async e=>{const edit=e.target.closest('[data-category-edit]'),del=e.target.closest('[data-category-delete]');if(edit)openCategory(categories.find(c=>c.id===Number(edit.dataset.categoryEdit)));if(del&&confirm('حذف هذا التصنيف الفارغ؟')){try{categories=(await request(route('categoryDestroy',del.dataset.categoryDelete),{},'DELETE')).categories;render();}catch(error){notice(error.message);}}};
  $('stickersGrid').onclick=async e=>{const b=e.target.closest('button');if(!b)return;if(b.dataset.edit||b.dataset.preview)openSticker(items().find(s=>s.id===Number(b.dataset.edit||b.dataset.preview)));if(b.dataset.delete&&confirm('حذف الملصق؟')){try{categories=(await request(route('itemDestroy',b.dataset.delete),{},'DELETE')).categories;render();}catch(error){notice(error.message);}}};
  function sizeRows(){
    $('sizesRows').innerHTML=settings.layout.sizes.map((s,i)=>`<div class="size-row" data-size-row><input type="hidden" data-size="key" value="${esc(s.key)}"><label>الاسم<input data-size="label" name="layout.sizes.${i}.label" value="${esc(s.label)}" required maxlength="80"></label><label>العرض mm<input data-size="width_mm" name="layout.sizes.${i}.width_mm" type="number" min="5" max="190" step="0.1" value="${s.width_mm}" required></label><label>الارتفاع mm<input data-size="height_mm" name="layout.sizes.${i}.height_mm" type="number" min="5" max="277" step="0.1" value="${s.height_mm}" required></label>${icon('trash','حذف المقاس',`data-size-delete="${i}"`)}</div>`).join('');
  }
  function fill(){$('settingsForm').querySelectorAll('.settings-grid [name]').forEach(input=>input.value=input.name.split('.').reduce((obj,key)=>obj?.[key],settings)??'');sizeRows();}
  function collect(){const value=structuredClone(settings);$('settingsForm').querySelectorAll('.settings-grid [name]').forEach(input=>{const [group,key]=input.name.split('.');value[group][key]=input.type==='color'?input.value:Number(input.value);});value.layout.sizes=[...document.querySelectorAll('[data-size-row]')].map(row=>Object.fromEntries([...row.querySelectorAll('[data-size]')].map(input=>[input.dataset.size,input.type==='number'?Number(input.value):input.value])));return value;}
  function changed(){dirty=true;version++;$('settingsSaveBar').hidden=false;$('settingsStatus').textContent='تغييرات غير محفوظة';schedule();}
  $('settingsForm').addEventListener('input',changed);
  $('addSize').onclick=()=>{settings=collect();if(settings.layout.sizes.length>=12)return;settings.layout.sizes.push({key:'size-'+Date.now(),label:'مقاس جديد',width_mm:50,height_mm:50});sizeRows();changed();};
  $('sizesRows').onclick=e=>{const b=e.target.closest('[data-size-delete]');if(!b)return;settings=collect();if(settings.layout.sizes.length===1)return notice('يجب إبقاء مقاس واحد على الأقل.');settings.layout.sizes.splice(Number(b.dataset.sizeDelete),1);sizeRows();changed();};
  function schedule(){clearTimeout(timer);if(currentTab!=='printing')return;const revision=++version;$('previewError').textContent='جار تحديث المعاينة…';timer=setTimeout(async()=>{try{const result=await request(config.routes.preview,collect());if(revision!==version)return;preview=result.layout;page=Math.min(page,preview.page_count-1);draw();$('previewError').textContent='';}catch(e){if(revision===version){$('previewError').textContent=e.message;$('adminA4Preview').replaceChildren();}}},350);}
  function draw(){if(!preview)return;const p=preview.page;$('adminA4Preview').innerHTML=`<svg class="admin-sheet-svg" viewBox="0 0 ${p.width_mm} ${p.height_mm}" role="img" aria-label="معاينة A4"><rect width="210" height="297" fill="white"/><rect x="${p.margins_mm.left}" y="${p.margins_mm.top}" width="${p.width_mm-p.margins_mm.left-p.margins_mm.right}" height="${p.height_mm-p.margins_mm.top-p.margins_mm.bottom}" fill="none" stroke="#94a3b8" stroke-width="0.3" stroke-dasharray="2 2"/>${preview.pages[page].items.map(item=>`<rect x="${item.x_mm}" y="${item.y_mm}" width="${item.width_mm}" height="${item.height_mm}" fill="#eff6ff" stroke="${esc(p.cut_line.color)}" stroke-width="${p.cut_line.width_mm}"/>`).join('')}</svg>`;$('previewPage').textContent=`${page+1} / ${preview.page_count}`;$('previewPrev').disabled=page===0;$('previewNext').disabled=page===preview.page_count-1;}
  $('previewPrev').onclick=()=>{page--;draw();};$('previewNext').onclick=()=>{page++;draw();};
  $('settingsForm').noValidate=true;
  $('settingsForm').onsubmit=async event=>{event.preventDefault();const button=event.submitter;if(button.disabled)return;button.disabled=true;$('settingsForm').querySelectorAll('.field-error').forEach(n=>n.remove());try{const result=await request(config.routes.settings,collect());settings=result.settings;fill();dirty=false;$('settingsSaveBar').hidden=true;notice('تم حفظ الإعدادات.');schedule();}catch(e){$('settingsStatus').textContent=e.message;errors($('settingsForm'),e);const field=Object.keys(e.fields||{})[0];if(field)tab(field.startsWith('layout.')?'printing':'pricing');}finally{button.disabled=false;}};
  window.addEventListener('beforeunload',event=>{if(dirty){event.preventDefault();event.returnValue='';}});
  render();fill();tab('library');
})();
