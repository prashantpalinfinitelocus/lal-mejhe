const experiencePages = document.getElementById("experiencePages");
const SERVER_BOOKING_CODE = experiencePages?.dataset.serverBookingCode || "";
// A booking grants one visit to page 4. Once the visitor leaves it, that booking
// code is marked consumed and cannot unlock page 4 again. A new booking code
// automatically grants access again. Keep the existing OTP/booking flow intact.
const ACTIVE_BOOKING_CODE = localStorage.getItem('bookingConfirmed') || SERVER_BOOKING_CODE || "";
const CONSUMED_BOOKING_KEY = 'lalMejheConsumedBookingCode';
let BOOKING_ACCESS = !!ACTIVE_BOOKING_CODE && localStorage.getItem(CONSUMED_BOOKING_KEY) !== ACTIVE_BOOKING_CODE;
(function(){
  const page = document.getElementById('page-section-5');
  if(page){
    page.classList.toggle('page-locked-v95', !BOOKING_ACCESS);
    page.dataset.bookingReady = BOOKING_ACCESS ? '1' : '0';
  }
  const modal=document.getElementById('bookingConfirmModal');
  const openModal=()=>{ if(!modal || !BOOKING_ACCESS)return; modal.classList.add('is-open'); modal.setAttribute('aria-hidden','false'); };
  const closeModal=()=>{ if(!modal)return; modal.classList.remove('is-open'); modal.setAttribute('aria-hidden','true'); };
  document.querySelectorAll('[data-close-booking]').forEach(b=>b.addEventListener('click',closeModal));
  if(modal && BOOKING_ACCESS){
    const code=SERVER_BOOKING_CODE || localStorage.getItem('bookingConfirmed') || 'CONFIRMED';
    document.getElementById('confirmBookingCode').textContent=code;
    let detail='Your booking has been confirmed. Please keep your booking code for reference.';
    try{ const d=JSON.parse(localStorage.getItem('lastBookingDetails')||'null'); if(d){ detail=[d.name,d.location,d.date,d.time,d.seats].filter(Boolean).map(x=>'<div>'+String(x).replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]))+'</div>').join(''); } }catch(e){}
    document.getElementById('confirmBookingDetails').innerHTML=detail;
  }
  window.openBookingConfirmation= openModal;

  // Open the existing confirmation card once after returning from success.php.
  // The success page writes bookingConfirmed to localStorage; do not alter booking or OTP flow.
  if (modal && BOOKING_ACCESS && localStorage.getItem('bookingConfirmed')) {
    const confirmedCode = localStorage.getItem('bookingConfirmed');
    const shownKey = 'lalMejheConfirmationShown:' + confirmedCode;
    if (sessionStorage.getItem(shownKey) !== '1') {
      window.addEventListener('DOMContentLoaded', function () {
        window.setTimeout(function () {
          openModal();
          if (modal.classList.contains('is-open')) {
            sessionStorage.setItem(shownKey, '1');
          }
        }, 350);
      }, { once: true });
    }
  }
})();
const LOCATION_DATA = JSON.parse(experiencePages?.dataset.locationData || "[]");
(function(){
  // No location is selected until the visitor taps a hotspot.
  const currentFirst=null;
  const title=document.getElementById('locationTitle');
  const desc=document.getElementById('locationDescription');
  const addr=document.getElementById('locationAddress');
  const map=document.getElementById('locationMap');
  const miniMap=document.getElementById('miniLocationMap');
  const dir=document.getElementById('directionBtn');
  const locationId=document.getElementById('location_id');
  const dateButtons=document.getElementById('dateButtons');
  const slotSelect=document.getElementById('slotSelect');
  const slotPills=document.getElementById('slotPills');
  const slotDateInput=document.getElementById('slotDateInput');
  const seatsInput=document.getElementById('seatsInput');
  const seatGrid=document.getElementById('seatGrid');
  const seatInfo=document.getElementById('seatInfo');
  const summary=document.getElementById('selectionSummary');
  const maxLabel=document.getElementById('seatMaxLabel');
  const seatHeading=maxLabel?.closest('h3');
  if(seatHeading && seatHeading.firstChild) seatHeading.firstChild.textContent='Select Seat No. ';
  const hotspotPins=document.getElementById('hotspotPins');
  const bookingForm=document.getElementById('bookingForm');
  let current=currentFirst, chosenDate='', bookedSeats=[];

  function selectedSeats(){
    return Array.from(seatGrid.querySelectorAll('.mini-seat.selected')).map(b=>b.dataset.seat);
  }
  function updateSeatsInput(){
    const s=selectedSeats();
    seatsInput.value=s.join(',');
    seatInfo.textContent=s.length ? 'Selected seats: '+s.join(', ') : 'Select one or more available seats.';
  }
  function buildSeats(max){
    seatGrid.innerHTML='';
    seatsInput.value='';
    if(!max){maxLabel.textContent='Choose a slot first';seatInfo.textContent='Select a slot to see available seats.';return;}
    for(let i=1;i<=max;i++){
      const b=document.createElement('button');
      b.type='button'; b.className='mini-seat'; b.dataset.seat=i; b.textContent=i;
      b.addEventListener('click',()=>{
        if(b.disabled)return;
        b.classList.toggle('selected');
        updateSeatsInput();
      });
      seatGrid.appendChild(b);
    }
    maxLabel.textContent='Maximum '+max+' seats';
    seatInfo.textContent='Loading seat availability…';
    loadBooked();
  }
  async function loadBooked(){
    const slot=slotSelect.value;
    if(!slot)return;
    bookedSeats=[];
    try{
      const r=await fetch('get_booked_seats.php?slot_id='+encodeURIComponent(slot),{cache:'no-store'});
      const data=await r.json();
      bookedSeats=(data.seats||[]).map(String);
      bookedSeats.forEach(n=>{
        const b=seatGrid.querySelector('[data-seat="'+n+'"]');
        if(b){b.disabled=true;b.classList.add('booked');}
      });
      updateSeatsInput();
    }catch(e){
      seatInfo.textContent='Seats are ready. Select your seats.';
    }
  }
  function normalizeDate(raw){
    const v=String(raw||'').trim();
    if(!v)return '';
    // Keep only the calendar date when MySQL/host returns a DATETIME value.
    const m=v.match(/^(\d{4}-\d{2}-\d{2})/);
    return m?m[1]:v;
  }
  function dateParts(raw){
    const d=normalizeDate(raw);
    const m=d.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if(!m)return null;
    const dt=new Date(Number(m[1]),Number(m[2])-1,Number(m[3]),12,0,0);
    if(Number.isNaN(dt.getTime()) || dt.getFullYear()!==Number(m[1]) || dt.getMonth()!==Number(m[2])-1 || dt.getDate()!==Number(m[3])) return null;
    return {value:d,dt};
  }
  function renderDates(){
    dateButtons.innerHTML='';
    if(!current){
      dateButtons.innerHTML='<span class="no-slots">Select a location pin first.</span>';
      slotSelect.disabled=true;
      slotSelect.innerHTML='<option value="">Select location first</option>';
      slotPills.innerHTML='<span class="slot-empty">Select location first</span>';
      return;
    }
    const dates=[...new Set((current?.slots||[]).map(s=>normalizeDate(s.slot_date)).filter(Boolean))]
      .map(dateParts).filter(Boolean).sort((a,b)=>a.dt-b.dt);
    if(!dates.length){
      dateButtons.innerHTML='<span class="no-slots">No future dates available for this location.</span>';
      slotSelect.disabled=true;
      slotSelect.innerHTML='<option value="">No slots available</option>';
      slotPills.innerHTML='<span class="slot-empty">No slots available</span>';
      return;
    }
    dates.forEach(item=>{
      const d=item.value, dt=item.dt;
      const b=document.createElement('button');
      b.type='button';
      b.className='date-btn';
      b.dataset.date=d;
      b.setAttribute('aria-label','Select '+dt.toLocaleDateString('en-IN',{weekday:'long',day:'numeric',month:'long',year:'numeric'}));
      const weekday=document.createElement('span');
      weekday.textContent=dt.toLocaleDateString('en-IN',{weekday:'short'}).toUpperCase();
      const day=document.createElement('strong');
      day.textContent=String(dt.getDate()).padStart(2,'0');
      const month=document.createElement('small');
      month.textContent=dt.toLocaleDateString('en-IN',{month:'short'});
      b.append(weekday,day,month);
      b.setAttribute('aria-pressed','false');
      b.addEventListener('click',()=>{
        // Single-date selection: clear every previous visual/ARIA selection first.
        dateButtons.querySelectorAll('.date-btn').forEach(btn=>{
          const active=btn.dataset.date===d;
          btn.classList.toggle('selected',active);
          btn.setAttribute('aria-pressed',active?'true':'false');
        });
        chooseDate(d);
        b.classList.remove('date-selected-animate');
        void b.offsetWidth;
        b.classList.add('date-selected-animate');
      });
      dateButtons.appendChild(b);
    });
  }
  function slotTimeParts(raw){
    const value=String(raw||'').trim();
    if(!value)return {start:'',end:''};
    const clean=value.replace(/[–—]/g,'-').replace(/\s+/g,' ').trim();
    const range=clean.match(/^(.+?)\s*-\s*(.+)$/);
    const start=range?range[1].trim():clean;
    const suppliedEnd=range?range[2].trim():'';
    const m=start.match(/^(\d{1,2})(?::(\d{2}))?\s*(AM|PM)$/i);
    if(!m)return {start,end:suppliedEnd};
    let hour=parseInt(m[1],10), minute=m[2]||'00', ap=m[3].toUpperCase();
    let nextHour=hour+1, nextAp=ap;
    if(nextHour===12 && ap==='AM') nextAp='PM';
    if(nextHour===13) nextHour=1;
    if(hour===11 && ap==='PM'){ nextHour=12; nextAp='AM'; }
    const startText=String(hour).padStart(2,'0')+':'+minute+' '+ap;
    const expectedEnd=String(nextHour).padStart(2,'0')+':'+minute+' '+nextAp;
    // Display-only repair: if Admin stored a one-hour range with the same
    // start/end time, show the correct one-hour end. Booking values are untouched.
    if(!suppliedEnd || suppliedEnd.toLowerCase()===startText.toLowerCase() || suppliedEnd.replace(/^0/,'').toLowerCase()===startText.replace(/^0/,'').toLowerCase())
      return {start:startText,end:expectedEnd};
    return {start:startText,end:suppliedEnd};
  }
  function displaySlotTime(raw){
    const parts=slotTimeParts(raw);
    return parts.end ? parts.start+' - '+parts.end : parts.start;
  }
  function chooseDate(d){
    chosenDate=d;slotDateInput.value=d;
    // Keep exactly one selected date, even if stale CSS/classes survived a cache update.
    dateButtons.querySelectorAll('.date-btn').forEach(b=>{
      const active=b.dataset.date===d;
      b.classList.toggle('selected',active);
      b.setAttribute('aria-pressed',active?'true':'false');
    });
    const rows=(current?.slots||[]).filter(s=>normalizeDate(s.slot_date)===d);
    slotSelect.innerHTML='<option value="">Select a time slot</option>';
    slotPills.innerHTML='';
    rows.forEach(s=>{
      const label=displaySlotTime(s.slot_time);
      const o=document.createElement('option');
      o.value=s.id;o.dataset.max=s.max_seats;o.textContent=label;slotSelect.appendChild(o);
      const pill=document.createElement('button');
      pill.type='button';pill.className='slot-btn';pill.dataset.slot=s.id;
      const parts=slotTimeParts(s.slot_time);
      if(parts.end){
        pill.innerHTML='<span class=\"slot-start\">'+parts.start+'</span><span class=\"slot-arrow\">→</span><span class=\"slot-end\">'+parts.end+'</span>';
        pill.title=label;
      }else{ pill.textContent=label; }
      pill.addEventListener('click',()=>chooseSlot(s.id));slotPills.appendChild(pill);
    });
    slotSelect.disabled=false;
    seatGrid.innerHTML='';seatsInput.value='';maxLabel.textContent='Choose a slot first';seatInfo.textContent='Select a slot to see available seats.';
    summary.textContent=rows.length+' one-hour slot'+(rows.length===1?'':'s')+' available for '+dtText(d)+'.';
  }
  function chooseSlot(id){
    slotSelect.value=String(id);
    document.querySelectorAll('.slot-btn').forEach(b=>b.classList.toggle('selected',b.dataset.slot===String(id)));
    const o=slotSelect.options[slotSelect.selectedIndex];
    if(!o||!o.value)return;
    buildSeats(parseInt(o.dataset.max||current.seat_quantity||15,10));
    summary.textContent='Date: '+dtText(chosenDate)+' • Slot: '+o.textContent;
  }
  function dtText(d){
    const item=dateParts(d);
    return item?item.dt.toLocaleDateString('en-IN',{day:'2-digit',month:'short',year:'numeric'}):String(d||'');
  }
  function renderPins(){
    hotspotPins.innerHTML='';
    LOCATION_DATA.forEach((l,i)=>{
      const b=document.createElement('button');
      b.type='button';b.className='hotspot-pin';
      b.style.left=(l.hotspot_left??50)+'%';b.style.top=(l.hotspot_top??50)+'%';
      b.title=l.name;b.setAttribute('aria-label',l.name);b.innerHTML='<img class="hotspot-pin-icon" src="/assets/hotspot-pin.png" alt="" aria-hidden="true">';
      b.addEventListener('click',()=>selectLocation(l.id));
      hotspotPins.appendChild(b);
    });
  }
  function selectLocation(id){
    current=LOCATION_DATA.find(x=>String(x.id)===String(id))||null;
    if(!current)return;
    locationId.value=current.id;
    title.innerHTML='EXPLORE<br>'+current.name.toUpperCase()+'<br>LAAL MEJHE';
    desc.textContent=current.description||'A heritage experience in the heart of Kolkata.';
    addr.textContent=current.address;
    map.src=current.map;miniMap.src=current.map;dir.href=current.direction;
    document.querySelectorAll('.hotspot-pin').forEach((p,i)=>p.classList.toggle('active',String(LOCATION_DATA[i].id)===String(current.id)));
    chosenDate='';slotDateInput.value='';slotSelect.innerHTML='<option value="">Select date first</option>';slotSelect.disabled=true;
    slotPills.innerHTML='<span class="slot-empty">Select date first</span>';seatGrid.innerHTML='';seatsInput.value='';
    maxLabel.textContent='Choose a slot first';seatInfo.textContent='Select a date and slot to see available seats.';
    summary.textContent='Select a date first.';
    renderDates();
    if(window.matchMedia('(max-width: 767px)').matches){
      window.setTimeout(()=>{
        const dateStep=dateButtons?.closest('.book-step');
        if(dateStep)dateStep.scrollIntoView({behavior:'smooth',block:'start'});
      },180);
    }
  }
  function showNoLocationSelected(){
    if(locationId)locationId.value='';
    if(title)title.textContent='Select the location pin to book your Laal Mejhe.';
    if(desc)desc.textContent='';
    if(addr)addr.textContent='';
    if(map)map.removeAttribute('src');
    if(miniMap)miniMap.removeAttribute('src');
    if(dir){dir.removeAttribute('href');dir.setAttribute('aria-disabled','true');}
    document.querySelectorAll('.hotspot-pin').forEach(p=>p.classList.remove('active'));
    chosenDate='';
    if(slotDateInput)slotDateInput.value='';
    if(slotSelect){slotSelect.innerHTML='<option value="">Select location first</option>';slotSelect.disabled=true;}
    if(slotPills)slotPills.innerHTML='<span class="slot-empty">Select location first</span>';
    if(seatGrid)seatGrid.innerHTML='';
    if(seatsInput)seatsInput.value='';
    if(maxLabel)maxLabel.textContent='Choose a location first';
    if(seatInfo)seatInfo.textContent='Select a location pin first.';
    if(summary)summary.textContent='Select the location pin to book your Laal Mejhe.';
    if(dateButtons)dateButtons.innerHTML='<span class="no-slots">Select a location pin first.</span>';
  }
  document.getElementById('seeMapBtn')?.addEventListener('click',()=>{
    document.getElementById('locationMap')?.scrollIntoView({behavior:'smooth',block:'center'});
  });
  renderPins();
  showNoLocationSelected();
  bookingForm?.addEventListener('submit',e=>{
    if(!current || !locationId.value){e.preventDefault();alert('Select the location pin to book your Laal Mejhe.');return;}
    if(!chosenDate){e.preventDefault();alert('Please select a date first.');return;}
    if(!slotSelect.value){e.preventDefault();alert('Please select a time slot.');return;}
    if(!seatsInput.value){e.preventDefault();alert('Please select at least one seat.');return;}
  });
  const authenticated=experiencePages?.dataset.authenticated==="1";
  const gn=document.getElementById('guest_name'),gm=document.getElementById('guest_mobile');
  if(gn)gn.value=experiencePages?.dataset.userName || "";
  if(gm)gm.value=experiencePages?.dataset.userMobile || "";
  const userModal=document.getElementById('userModal');
  if(authenticated){ userModal?.classList.add('hidden'); }
  else { userModal?.classList.remove('hidden'); }
})();
async function sendOtp(){
  const mobile=document.getElementById('guestMobile').value.replace(/\D/g,'');
  if(!/^[6-9]\d{9}$/.test(mobile)){alert('Please enter a valid 10-digit Indian mobile number.');return}
  const btn=document.getElementById('sendOtpBtn'), resend=document.getElementById('resendOtpBtn');
  if(btn){btn.disabled=true;btn.innerText='SENDING OTP...'} if(resend)resend.disabled=true;
  try{
    const fd=new FormData();
    fd.append('csrf_token',document.querySelector('meta[name=csrf-token]')?.content||'');
    fd.append('mobile','91'+mobile);
    const r=await fetch('send_otp.php',{method:'POST',body:fd,headers:{'Accept':'application/json'}});
    const raw=await r.text(); let d={};
    try{d=JSON.parse(raw)}catch(_){throw new Error('OTP service returned an invalid response. Please refresh the page and try again.')}
    if(!r.ok || !d.ok)throw new Error(d.message||'Unable to send OTP');
    document.getElementById('mobileStep').classList.add('hidden');
    document.getElementById('otpVerifyStep').classList.remove('hidden');
    document.getElementById('otpMessage').textContent=d.message||'Enter the OTP sent to your mobile.';
    document.getElementById('guestOtp').value='';
    document.getElementById('guestOtp').focus();
    startOtpCooldown();
  }catch(e){alert(e.message||'Unable to send OTP.')}
  finally{if(btn){btn.disabled=false;btn.innerText='SEND OTP →'}}
}
let otpCooldownTimer=null;
function startOtpCooldown(){
  const b=document.getElementById('resendOtpBtn'); if(!b)return; let left=30; b.disabled=true;
  clearInterval(otpCooldownTimer); b.textContent='RESEND OTP ('+left+'s)';
  otpCooldownTimer=setInterval(()=>{left--;if(left<=0){clearInterval(otpCooldownTimer);b.disabled=false;b.textContent='RESEND OTP'}else b.textContent='RESEND OTP ('+left+'s)'},1000);
}
function changeMobile(){
  clearInterval(otpCooldownTimer);
  document.getElementById('otpVerifyStep').classList.add('hidden');
  document.getElementById('signupStep').classList.add('hidden');
  document.getElementById('mobileStep').classList.remove('hidden');
  document.getElementById('guestOtp').value='';
  document.getElementById('guestMobile').focus();
}
async function verifyOtp(){
  const mobile=document.getElementById('guestMobile').value.replace(/\D/g,'');
  const otp=document.getElementById('guestOtp').value.replace(/\D/g,'');
  if(!/^[6-9]\d{9}$/.test(mobile)){alert('Invalid mobile number.');return}
  if(!/^\d{6}$/.test(otp)){alert('Please enter the 6-digit OTP.');return}
  const btn=document.getElementById('verifyOtpBtn'); if(btn){btn.disabled=true;btn.innerText='VERIFYING...'}
  try{
    const fd=new FormData();fd.append('csrf_token',document.querySelector('meta[name=csrf-token]')?.content||'');fd.append('mobile','91'+mobile);fd.append('otp',otp);
    const r=await fetch('verify_otp.php',{method:'POST',body:fd,headers:{'Accept':'application/json'}}); const d=await r.json();
    if(!r.ok || !d.ok)throw new Error(d.message||'OTP verification failed');
    clearInterval(otpCooldownTimer);
    document.getElementById('otpVerifyStep').classList.add('hidden');

    /* Returning users: OTP is the only authentication step. The server has
     * already loaded the saved name/email into the session, so never show the
     * first-time registration form again. */
    if(d.returning_user){
      document.getElementById('userModal').classList.add('hidden');
      const gn=document.getElementById('guest_name'),gm=document.getElementById('guest_mobile');
      if(gn)gn.value=d.name||experiencePages?.dataset.userName||"";
      if(gm)gm.value='91'+mobile;
      localStorage.removeItem('guest');
      window.location.reload();
      return;
    }

    document.getElementById('signupStep').classList.remove('hidden');
    document.getElementById('termsPrivacyConsent').focus();
  }catch(e){alert(e.message||'OTP verification failed.')}
  finally{if(btn){btn.disabled=false;btn.innerText='VERIFY OTP →'}}
}
async function submitSignup(){
  const mobile=document.getElementById('guestMobile').value.replace(/\D/g,'');
  const consent=document.getElementById('termsPrivacyConsent').checked;
  if(!/^[6-9]\d{9}$/.test(mobile)){alert('Invalid mobile number.');return}
  if(!consent){alert('Please accept the Terms & Conditions and Privacy Policy to continue.');return}
  const btn=document.getElementById('signupSubmitBtn'); if(btn){btn.disabled=true;btn.innerText='SAVING...'}
  try{
    const fd=new FormData();
    fd.append('csrf_token',document.querySelector('meta[name=csrf-token]')?.content||'');
    fd.append('terms_privacy','1');
    const r=await fetch('save_signup.php',{method:'POST',body:fd,headers:{'Accept':'application/json'}});
    const raw=await r.text(); let d={};
    try{d=JSON.parse(raw)}catch(_){throw new Error(r.status===404?'Consent service file is missing. Please upload save_signup.php.':'Consent service returned an invalid response. Please refresh and try again.')}
    if(!r.ok || !d.ok)throw new Error(d.message||'Unable to save consent');
    localStorage.removeItem('guest');
    const gm=document.getElementById('guest_mobile'); if(gm)gm.value='91'+mobile;
    document.getElementById('userModal').classList.add('hidden');
    window.location.reload();
  }catch(e){alert(e.message||'Unable to save consent. Please try again.')}
  finally{if(btn){btn.disabled=false;btn.innerText='CONTINUE'}}
}

// V115 — strict manual slide navigation. No document scrolling; NEXT/BACK only.
(function(){
  const pages=[...document.querySelectorAll('.experience-page-v94')];
  if(!pages.length)return;

  function pageNumberFromUrl(){
    const n=parseInt(new URLSearchParams(window.location.search).get('page')||'1',10);
    return Number.isFinite(n) ? Math.max(1,Math.min(n,pages.length)) : 1;
  }

  function setCurrent(index){
    pages.forEach((page,i)=>{
      const active=i===index;
      page.classList.toggle('nav-current',active);
      page.classList.toggle('is-active',active);
      page.setAttribute('aria-hidden',active?'false':'true');
      page.querySelectorAll('.page-nav-v94[data-next], .page-nav-v94[data-prev]').forEach(btn=>{
        const disabled=(btn.hasAttribute('data-prev') && i===0) || (btn.hasAttribute('data-next') && i===pages.length-1);
        btn.disabled=disabled;
        btn.setAttribute('aria-disabled',disabled?'true':'false');
      });
    });
  }

  function goTo(index,updateUrl=true){
    let target=Math.max(0,Math.min(index,pages.length-1));
    const finalPageIndex=pages.findIndex(p=>p.id==='page-section-5');
    const currentIndex=pages.findIndex(p=>p.classList.contains('is-active') || p.classList.contains('nav-current'));

    // Leaving the confirmation page consumes this booking's one-time access.
    if(currentIndex===finalPageIndex && target!==finalPageIndex && ACTIVE_BOOKING_CODE){
      localStorage.setItem(CONSUMED_BOOKING_KEY, ACTIVE_BOOKING_CODE);
      BOOKING_ACCESS=false;
      const finalPage=pages[finalPageIndex];
      if(finalPage){
        finalPage.classList.add('page-locked-v95');
        finalPage.dataset.bookingReady='0';
      }
    }

    // Direct URL, browser Back/Forward, and NEXT cannot reopen page 4 after
    // its booking has been consumed. Send the visitor to the booking page.
    if(target===finalPageIndex && !BOOKING_ACCESS){
      target=Math.max(0,finalPageIndex-1);
    }
    setCurrent(target);
    if(updateUrl) history.replaceState({page:target+1},'',window.location.pathname+'?page='+(target+1));
  }

  document.addEventListener('click',function(event){
    const btn=event.target.closest('.page-nav-v94[data-next], .page-nav-v94[data-prev]');
    if(!btn || btn.disabled)return;
    const page=btn.closest('.experience-page-v94');
    const index=pages.indexOf(page);
    if(index<0)return;
    event.preventDefault();
    event.stopImmediatePropagation();
    goTo(index+(btn.hasAttribute('data-next')?1:-1),true);
  },true);

  window.addEventListener('popstate',function(){ goTo(pageNumberFromUrl()-1,false); });

  // Keep the document fixed. Section 4 itself remains scrollable for its booking controls.
  document.documentElement.classList.add('lal-slide-mode-v115');
  document.body.classList.add('lal-slide-mode-v115');
  goTo(pageNumberFromUrl()-1,false);
})();

// Artwork fallback: if a newly supplied artwork was not uploaded, use the existing artwork file.
(function(){
  document.querySelectorAll('.artwork-page-v94 picture').forEach(function(picture){
    const source=picture.querySelector('source[data-fallback]');
    const img=picture.querySelector('img[data-fallback]');
    if(img){
      img.addEventListener('error',function(){
        const fallback=img.getAttribute('data-fallback');
        if(fallback && img.getAttribute('src')!==fallback){
          if(source) source.setAttribute('srcset',source.getAttribute('data-fallback')||'');
          img.src=fallback;
        }
      },{once:true});
    }
  });
})();

document.addEventListener("DOMContentLoaded", function(){
  document.getElementById("sendOtpBtn")?.addEventListener("click", sendOtp);
  document.getElementById("resendOtpBtn")?.addEventListener("click", sendOtp);
  document.getElementById("verifyOtpBtn")?.addEventListener("click", verifyOtp);
  document.getElementById("changeMobileBtn")?.addEventListener("click", changeMobile);
  document.getElementById("signupSubmitBtn")?.addEventListener("click", submitSignup);
});
