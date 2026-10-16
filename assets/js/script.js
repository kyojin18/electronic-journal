function toggleMenu(){document.getElementById('sidebar')?.classList.toggle('open')}
function toast(message, ok=true){
 const t=document.getElementById('toast'); if(!t)return;
 t.textContent=(ok?'✓ ':'✕ ')+message;t.classList.add('show');
 setTimeout(()=>t.classList.remove('show'),2500);
}
function filterTable(inputId, tableId){
 const q=document.getElementById(inputId).value.toLowerCase();
 document.querySelectorAll(`#${tableId} tbody tr`).forEach(tr=>{
   tr.style.display=tr.innerText.toLowerCase().includes(q)?'':'none';
 });
}
async function saveAttendance(form){
 const submit=form.querySelector('button[type="submit"],button:not([type])');
 if(submit){submit.disabled=true;submit.dataset.oldText=submit.textContent;submit.textContent='Сохранение...';}
 try{
   const fd=new FormData(form);
   const res=await fetch('../api/attendance_save.php',{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}});
   const text=await res.text();
   let data;
   try{data=JSON.parse(text);}catch(e){throw new Error('Сервер вернул некорректный ответ');}
   toast(data.message||'Готово',!data.error);
   if(!data.error)setTimeout(()=>location.reload(),650);
 }catch(e){toast(e.message||'Не удалось сохранить посещаемость',false);}
 finally{if(submit){submit.disabled=false;submit.textContent=submit.dataset.oldText||'Сохранить';}}
 return false;
}
function openLogoutModal(){
 const m=document.getElementById('logoutModal');
 if(m)m.classList.add('show');
}
function closeLogoutModal(){
 const m=document.getElementById('logoutModal');
 if(m)m.classList.remove('show');
}
document.addEventListener('click',e=>{
 const m=document.getElementById('logoutModal');
 if(m && e.target===m) closeLogoutModal();
});
document.addEventListener('keydown',e=>{
 if(e.key==='Escape') closeLogoutModal();
});
function updatePasswordStrength(value){
 const el=document.getElementById('passwordStrength');if(!el)return;
 let score=0;if(value.length>=6)score++;if(value.length>=10)score++;if(/[A-ZА-Я]/.test(value)&&/[a-zа-я]/.test(value))score++;if(/\d/.test(value))score++;if(/[^A-Za-zА-Яа-я0-9]/.test(value))score++;
 const widths=[0,20,40,65,85,100];el.style.width=widths[Math.min(score,5)]+'%';
 if(score>=4)el.style.background='#16a34a';else if(score>=2)el.style.background='#f59e0b';else el.style.background='#ef4444';
}

/* Поиск по критериям в таблицах */
(function(){
  function clean(s){ return String(s || '').replace(/\s+/g,' ').trim(); }

  function getTableId(input){
    if(input.dataset.filterTable) return input.dataset.filterTable;
    const code = input.getAttribute('oninput') || '';
    const m = code.match(/filterTable\(\s*['\"][^'\"]+['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/);
    return m ? m[1] : '';
  }

  function getHeaders(table){
    let row = table.querySelector('thead tr');
    if(!row) row = table.querySelector('tr');
    if(!row) return [];
    return Array.from(row.children).map((cell,index)=>({
      index,
      label: clean(cell.textContent)
    })).filter(x=>x.label && !['id','№','действия','действие'].includes(x.label.toLowerCase()));
  }

  function uniqueColumnValues(table, col){
    const values = new Set();
    const rows = Array.from(table.querySelectorAll('tbody tr'));
    const actualRows = rows.length ? rows : Array.from(table.querySelectorAll('tr')).slice(1);
    actualRows.forEach(tr=>{
      if(tr.querySelector('th')) return;
      const td = tr.children[col];
      if(!td) return;
      const v = clean(td.textContent);
      if(v && v !== '—' && v.length <= 120) values.add(v);
    });
    return Array.from(values).sort((a,b)=>a.localeCompare(b,'ru',{numeric:true}));
  }

  function populateValueSelect(input){
    const criterion = document.getElementById(input.dataset.criteriaSelect);
    const valueSelect = document.getElementById(input.dataset.valueSelect);
    const table = document.getElementById(input.dataset.filterTable);
    if(!criterion || !valueSelect || !table) return;

    valueSelect.innerHTML = '<option value="">Все значения</option>';
    if(criterion.value === ''){
      valueSelect.disabled = true;
      return;
    }
    valueSelect.disabled = false;
    uniqueColumnValues(table, Number(criterion.value)).forEach(v=>{
      const opt=document.createElement('option');
      opt.value=v; opt.textContent=v;
      valueSelect.appendChild(opt);
    });
  }

  window.filterTable = function(inputId, tableId){
    const input=document.getElementById(inputId);
    const table=document.getElementById(tableId);
    if(!input || !table) return;
    const q=clean(input.value).toLowerCase();
    const criterion=document.getElementById(input.dataset.criteriaSelect || '');
    const valueSelect=document.getElementById(input.dataset.valueSelect || '');
    const col=criterion && criterion.value!=='' ? Number(criterion.value) : null;
    const selected=clean(valueSelect?.value).toLowerCase();

    let rows=Array.from(table.querySelectorAll('tbody tr'));
    if(!rows.length) rows=Array.from(table.querySelectorAll('tr')).slice(1);
    const eligibleCols=getHeaders(table).map(h=>h.index);
    rows.forEach(tr=>{
      if(tr.querySelector('th')) return;
      const cells=Array.from(tr.children);
      const searchable = col===null ? eligibleCols.map(i=>clean(cells[i]?.textContent)).join(' ') : clean(cells[col]?.textContent);
      const hay=searchable.toLowerCase();
      const textOk=!q || hay.includes(q);
      const valueOk=!selected || (col!==null && clean(cells[col]?.textContent).toLowerCase()===selected);
      tr.style.display=(textOk && valueOk)?'':'none';
    });
  };

  function setup(input){
    if(input.dataset.criteriaReady) return;
    const tableId=getTableId(input);
    const table=document.getElementById(tableId);
    if(!table) return;
    input.dataset.criteriaReady='1';
    input.dataset.filterTable=tableId;
    input.setAttribute('autocomplete','off');
    input.setAttribute('spellcheck','false');

    const headers=getHeaders(table);
    if(!headers.length) return;
    const base=input.id || ('search_'+Math.random().toString(36).slice(2));
    if(!input.id) input.id=base;

    const criterion=document.createElement('select');
    criterion.className='search-criterion';
    criterion.id=base+'_criterion';
    criterion.innerHTML='<option value="">Все поля</option>';
    headers.forEach(h=>{
      const o=document.createElement('option');
      o.value=String(h.index); o.textContent=h.label;
      criterion.appendChild(o);
    });

    const value=document.createElement('select');
    value.className='search-value';
    value.id=base+'_value';
    value.innerHTML='<option value="">Все значения</option>';
    value.disabled=true;

    input.dataset.criteriaSelect=criterion.id;
    input.dataset.valueSelect=value.id;

    const container=document.createElement('div');
    container.className='criteria-controls';
    input.insertAdjacentElement('afterend',container);
    container.appendChild(criterion);
    container.appendChild(value);

    criterion.addEventListener('change',()=>{
      populateValueSelect(input);
      window.filterTable(input.id,tableId);
    });
    value.addEventListener('change',()=>window.filterTable(input.id,tableId));
  }

  document.addEventListener('DOMContentLoaded',()=>{
    document.querySelectorAll('input[name="q"], input[type="search"]').forEach(i=>{i.setAttribute('autocomplete','off');i.setAttribute('spellcheck','false');});
    document.querySelectorAll('input.search[id][oninput*="filterTable"]').forEach(setup);
  });
})();
