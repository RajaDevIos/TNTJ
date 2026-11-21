// Client Application Controller for TV TNTJ Portal

document.addEventListener('DOMContentLoaded', () => {
  // Navigation elements
  const navLinks = document.querySelectorAll('.nav-link, .drawer-link, [data-target]');
  const sections = document.querySelectorAll('.tab-section');
  const mobileToggle = document.getElementById('mobileToggle');
  const drawerPanel = document.getElementById('mobileDrawerPanel');
  const drawerBackdrop = document.getElementById('drawerBackdrop');
  const closeDrawer = document.getElementById('closeDrawer');

  // Modal elements
  const bloodModal = document.getElementById('bloodRequestModal');
  const btnOpenBloodModal = document.querySelectorAll('.btn-open-blood-modal');
  const btnCloseBloodModal = document.getElementById('closeBloodModal');
  const bloodRequestForm = document.getElementById('bloodRequestForm');

  // Active navigation switcher
  function activateSection(targetId) {
    if (!targetId) targetId = 'home';

    sections.forEach(sec => {
      if (sec.id === targetId) {
        sec.classList.add('active');
      } else {
        sec.classList.remove('active');
      }
    });

    document.querySelectorAll('.nav-link, .drawer-link').forEach(link => {
      if (link.getAttribute('data-target') === targetId) {
        link.classList.add('active');
      } else {
        link.classList.remove('active');
      }
    });

    // Close mobile drawer
    closeMobileDrawer();

    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  // Handle nav clicks
  navLinks.forEach(link => {
    link.addEventListener('click', (e) => {
      const target = link.getAttribute('data-target');
      if (target) {
        e.preventDefault();
        window.location.hash = target;
        activateSection(target);
      }
    });
  });

  // Mobile Drawer controls
  function openMobileDrawer() {
    if (drawerPanel && drawerBackdrop) {
      drawerPanel.classList.add('open');
      drawerBackdrop.classList.add('open');
      document.body.style.overflow = 'hidden';
    }
  }

  function closeMobileDrawer() {
    if (drawerPanel && drawerBackdrop) {
      drawerPanel.classList.remove('open');
      drawerBackdrop.classList.remove('open');
      document.body.style.overflow = '';
    }
  }

  if (mobileToggle) mobileToggle.addEventListener('click', openMobileDrawer);
  if (closeDrawer) closeDrawer.addEventListener('click', closeMobileDrawer);
  if (drawerBackdrop) drawerBackdrop.addEventListener('click', closeMobileDrawer);

  // Keyboard accessibility: ESC closes drawer & modal
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      closeMobileDrawer();
      closeBloodModalOverlay();
    }
  });

  // Blood Request Modal Handlers
  function openBloodModalOverlay() {
    if (bloodModal) {
      bloodModal.classList.add('open');
      document.body.style.overflow = 'hidden';
    }
  }

  function closeBloodModalOverlay() {
    if (bloodModal) {
      bloodModal.classList.remove('open');
      document.body.style.overflow = '';
    }
  }

  btnOpenBloodModal.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      openBloodModalOverlay();
    });
  });

  if (btnCloseBloodModal) {
    btnCloseBloodModal.addEventListener('click', closeBloodModalOverlay);
  }

  if (bloodModal) {
    bloodModal.addEventListener('click', (e) => {
      if (e.target === bloodModal) closeBloodModalOverlay();
    });
  }

  // Blood Request Form Submit (generate WhatsApp message to coordinator)
  if (bloodRequestForm) {
    bloodRequestForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const patientName = document.getElementById('reqPatientName').value.trim();
      const bloodGroup = document.getElementById('reqBloodGroup').value;
      const units = document.getElementById('reqUnits').value.trim();
      const hospital = document.getElementById('reqHospital').value.trim();
      const bystanderPhone = document.getElementById('reqContactPhone').value.trim();

      const message = `🚨 அவசர இரத்த உதவி கோரிக்கை (TV TNTJ)%0A` +
        `👤 நோயாளி: ${encodeURIComponent(patientName)}%0A` +
        `🩸 இரத்தப்பிரிவு: ${encodeURIComponent(bloodGroup)} (${encodeURIComponent(units)} யூனிட்)%0A` +
        `🏥 மருத்துவமனை: ${encodeURIComponent(hospital)}%0A` +
        `📞 தொடர்பு எண்: ${encodeURIComponent(bystanderPhone)}`;

      const coordinatorPhone = "919842400006";
      const waUrl = `https://wa.me/${coordinatorPhone}?text=${message}`;

      closeBloodModalOverlay();
      bloodRequestForm.reset();

      // Open WhatsApp or prompt
      alert("✅ உங்கள் அவசர இரத்த கோரிக்கை தயாராக உள்ளது. மாவட்ட இரத்ததான ஒருங்கிணைப்பாளரின் வாட்ஸ்அப் (WhatsApp) இணைப்பிற்கு மாற்றப்படுகிறீர்கள்.");
      window.open(waUrl, '_blank');
    });
  }

  // Initial Hash routing
  const initialHash = window.location.hash.replace('#', '') || 'home';
  activateSection(initialHash);

  window.addEventListener('hashchange', () => {
    const hash = window.location.hash.replace('#', '') || 'home';
    activateSection(hash);
  });

  // Render all portal components
  renderPrayerTimes();
  renderPolicySection();
  renderDistrictExecutivesSection();
  renderDistrictDaeesSection();
  renderBranchExecutivesSection();
  renderCollegesSection();
  renderMaktabSection();
  renderBloodDonorsSection();
  renderMatrimonialSection();
  renderFormsSection();
  renderEventsSection();
  renderStatsSection();
  renderLinksSection();

  // Blood Donor Search and Filter Listeners
  const bloodFilterGroup = document.getElementById('bloodFilterGroup');
  const bloodSearchInput = document.getElementById('bloodSearchInput');

  function updateBloodDonorFilter() {
    const grp = bloodFilterGroup ? bloodFilterGroup.value : 'all';
    const query = bloodSearchInput ? bloodSearchInput.value.toLowerCase().trim() : '';

    const filtered = PORTAL_DATA.bloodDonors.filter(donor => {
      const matchGrp = grp === 'all' || donor.group.toUpperCase() === grp.toUpperCase();
      const matchQuery = donor.name.toLowerCase().includes(query) ||
                         donor.branch.toLowerCase().includes(query) ||
                         donor.group.toLowerCase().includes(query);
      return matchGrp && matchQuery;
    });

    renderBloodDonorCards(filtered);
  }

  if (bloodFilterGroup) bloodFilterGroup.addEventListener('change', updateBloodDonorFilter);
  if (bloodSearchInput) bloodSearchInput.addEventListener('input', updateBloodDonorFilter);

  // Branch Executives Search
  const branchSearchInput = document.getElementById('branchSearchInput');
  const branchTalukFilter = document.getElementById('branchTalukFilter');

  function updateBranchFilter() {
    const query = branchSearchInput ? branchSearchInput.value.toLowerCase().trim() : '';
    const taluk = branchTalukFilter ? branchTalukFilter.value : 'all';

    const filtered = PORTAL_DATA.branchExecutives.filter(b => {
      const matchTaluk = taluk === 'all' || b.taluk === taluk;
      const matchQuery = b.branch.toLowerCase().includes(query) ||
                         b.president.toLowerCase().includes(query) ||
                         b.secretary.toLowerCase().includes(query) ||
                         b.taluk.toLowerCase().includes(query);
      return matchTaluk && matchQuery;
    });

    renderBranchTable(filtered);
  }

  if (branchSearchInput) branchSearchInput.addEventListener('input', updateBranchFilter);
  if (branchTalukFilter) branchTalukFilter.addEventListener('change', updateBranchFilter);
});

// Render Component Helpers

function renderPrayerTimes() {
  const container = document.getElementById('prayerTimesContainer');
  if (!container) return;

  container.innerHTML = PORTAL_DATA.prayerTimes.times.map((p, idx) => `
    <span class="prayer-pill ${idx === 1 ? 'active' : ''}">
      <strong>${p.name}:</strong> ${p.time}
    </span>
  `).join('');
}

function renderPolicySection() {
  const container = document.getElementById('policyList');
  if (!container) return;

  container.innerHTML = PORTAL_DATA.policy.points.map(pt => `
    <div class="portal-card" style="margin-bottom: 18px; border-left: 5px solid var(--primary-700);">
      <div class="portal-card-header">
        <h3 class="portal-card-title">${pt.title}</h3>
        <span class="badge badge-emerald">${pt.badge}</span>
      </div>
      <p class="portal-card-desc" style="color: var(--text-secondary); margin-bottom: 0;">${pt.desc}</p>
    </div>
  `).join('');
}

function renderDistrictExecutivesSection() {
  const container = document.getElementById('distExecGrid');
  if (!container) return;

  container.innerHTML = PORTAL_DATA.districtExecutives.map(exec => `
    <div class="portal-card">
      <div class="portal-card-header">
        <span class="badge badge-emerald">${exec.role}</span>
        <span style="font-size: 0.8rem; color: var(--text-muted);">📍 ${exec.area}</span>
      </div>
      <h3 class="portal-card-title" style="margin-bottom: 6px;">${exec.name}</h3>
      <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 16px;">✉️ ${exec.email}</p>
      
      <div style="margin-top: auto; display: flex; gap: 8px;">
        <a href="tel:${exec.phone.replace(/[^0-9+]/g, '')}" class="btn-primary" style="flex: 1; justify-content: center; font-size: 0.84rem; padding: 8px 12px;">
          📞 அழைக்க
        </a>
        <a href="https://wa.me/${exec.phone.replace(/[^0-9]/g, '')}" target="_blank" class="btn-outline" style="padding: 8px 12px; font-size: 0.84rem;">
          💬 WhatsApp
        </a>
      </div>
    </div>
  `).join('');
}

function renderDistrictDaeesSection() {
  const container = document.getElementById('daeesGrid');
  if (!container) return;

  container.innerHTML = PORTAL_DATA.districtDaees.map(daee => `
    <div class="portal-card" style="border-top: 4px solid var(--primary-600);">
      <div class="portal-card-header">
        <span class="badge badge-indigo">${daee.role}</span>
        <span style="font-size: 0.78rem; color: var(--text-muted);">${daee.exp}</span>
      </div>
      <h3 class="portal-card-title" style="margin-bottom: 8px;">${daee.name}</h3>
      <div style="background: var(--surface-alt); padding: 10px 12px; border-radius: var(--radius-xs); margin-bottom: 14px; font-size: 0.86rem; color: var(--text-secondary);">
        <strong>சிறப்புத் துறை:</strong> ${daee.specialization}
      </div>
      <div style="margin-top: auto;">
        <a href="tel:${daee.phone.replace(/[^0-9+]/g, '')}" class="btn-outline" style="width: 100%; justify-content: center; font-size: 0.84rem; padding: 8px 12px;">
          🎤 பயான் / வகுப்புகளுக்கு அழைக்க
        </a>
      </div>
    </div>
  `).join('');
}

function renderBranchTable(branches = PORTAL_DATA.branchExecutives) {
  const container = document.getElementById('branchTableBody');
  if (!container) return;

  if (branches.length === 0) {
    container.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 30px; color: var(--text-muted);">கிளைகள் எதுவும் கண்டறியப்படவில்லை.</td></tr>`;
    return;
  }

  container.innerHTML = branches.map((b, i) => `
    <tr>
      <td style="font-weight: 700;">${i + 1}</td>
      <td style="font-weight: 700; color: var(--primary-900);">${b.branch}</td>
      <td><span class="badge" style="background: #f1f5f9; color: #475569;">${b.taluk}</span></td>
      <td>${b.president}</td>
      <td>${b.secretary}</td>
      <td>
        <a href="tel:${b.phone}" style="color: var(--primary-700); text-decoration: none; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
          📞 ${b.phone}
        </a>
      </td>
    </tr>
  `).join('');
}

function renderBranchExecutivesSection() {
  renderBranchTable();
}

function renderCollegesSection() {
  const container = document.getElementById('collegesGrid');
  if (!container) return;

  container.innerHTML = PORTAL_DATA.colleges.map(c => `
    <div class="portal-card" style="border-top: 5px solid var(--primary-800);">
      <div class="portal-card-header">
        <h3 class="portal-card-title" style="font-size: 1.25rem;">${c.name}</h3>
        <span class="badge badge-emerald">${c.seats}</span>
      </div>
      <p style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 12px;">📍 ${c.location}</p>
      
      <div style="background: var(--primary-50); border: 1px solid var(--primary-100); border-radius: var(--radius-sm); padding: 14px; margin-bottom: 14px;">
        <strong style="color: var(--primary-900); font-size: 0.9rem;">பாடத்திட்டம்:</strong>
        <p style="font-size: 0.9rem; color: var(--primary-800); margin-top: 4px; font-weight: 600;">${c.course}</p>
        <p style="font-size: 0.82rem; color: var(--text-muted); margin-top: 6px;">தகுதி: ${c.eligibility}</p>
      </div>

      <p style="font-size: 0.88rem; color: var(--text-secondary); margin-bottom: 18px; line-height: 1.6;">${c.facilities}</p>

      <div style="margin-top: auto; padding-top: 14px; border-top: 1px solid var(--surface-border-subtle); display: flex; justify-content: space-between; align-items: center;">
        <span style="font-size: 0.84rem; font-weight: 700; color: var(--primary-700);">📞 ${c.contact}</span>
        <button onclick="alert('மாணவர் சேர்க்கை விபரங்களுக்கு கல்லூரி அலுவலகத்தை தொடர்பு கொள்ளவும்: ${c.contact}')" class="btn-primary" style="font-size: 0.82rem; padding: 7px 14px;">
          விண்ணப்பிக்க
        </button>
      </div>
    </div>
  `).join('');
}

function renderMaktabSection() {
  const container = document.getElementById('maktabGrid');
  if (!container) return;

  container.innerHTML = PORTAL_DATA.maktab.map(m => `
    <div class="portal-card">
      <div class="portal-card-header">
        <h3 class="portal-card-title">${m.name}</h3>
        <span class="badge badge-emerald">${m.students}</span>
      </div>
      <p style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 10px;">⏰ நேரம்: <strong>${m.timing}</strong></p>
      <p style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 14px;">${m.curriculum}</p>
      <div style="margin-top: auto; font-size: 0.82rem; color: var(--primary-700); font-weight: 700;">
        பொறுப்பாளர்: ${m.incharge}
      </div>
    </div>
  `).join('');
}

function renderBloodDonorCards(donors = PORTAL_DATA.bloodDonors) {
  const container = document.getElementById('bloodDonorsGrid');
  if (!container) return;

  if (donors.length === 0) {
    container.innerHTML = `
      <div style="grid-column: 1/-1; text-align: center; padding: 40px; background: #ffffff; border-radius: var(--radius-md); border: 1px dashed var(--surface-border);">
        <p style="font-size: 1.1rem; font-weight: 700; color: var(--text-primary); margin-bottom: 8px;">தங்கள் தேடலுக்கு தற்பொழுது கொடையாளர்கள் கிடைக்கவில்லை</p>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 16px;">அவசர தேவைக்கு மாவட்ட இரத்ததான சேவை ஒருங்கிணைப்பாளரை நேரடியாக அழைக்கவும்.</p>
        <a href="tel:+919842400006" class="btn-blood-req" style="display: inline-flex; margin: 0 auto;">📞 +91 98424 00006 அழைக்க</a>
      </div>
    `;
    return;
  }

  container.innerHTML = donors.map(d => `
    <div class="portal-card" style="border-left: 4px solid var(--crimson-600);">
      <div class="portal-card-header">
        <div>
          <h4 style="font-size: 1.1rem; font-weight: 800; color: var(--text-primary);">${d.name}</h4>
          <span style="font-size: 0.82rem; color: var(--text-muted);">📍 ${d.branch} கிளை</span>
        </div>
        <span class="badge badge-red" style="font-size: 0.9rem; padding: 4px 10px;">${d.group}</span>
      </div>
      <p style="font-size: 0.82rem; color: #15803d; font-weight: 600; margin-bottom: 14px;">✓ ${d.lastDonated}</p>
      
      <div style="margin-top: auto; display: flex; gap: 8px;">
        <a href="tel:${d.phone}" class="btn-blood-req" style="flex: 1; justify-content: center; font-size: 0.82rem; padding: 8px 12px;">
          📞 அழைக்க
        </a>
        <a href="https://wa.me/91${d.phone}?text=அஸ்ஸலாமு அலைக்கும் சகோதரரே, TV TNTJ அவசர இரத்ததான சேவை மூலம் தொடர்பு கொள்கிறோம்." target="_blank" class="btn-outline" style="padding: 8px 12px; font-size: 0.82rem;">
          💬 WhatsApp
        </a>
      </div>
    </div>
  `).join('');
}

function renderBloodDonorsSection() {
  renderBloodDonorCards();
}

function renderMatrimonialSection() {
  const container = document.getElementById('matrimonialGrid');
  if (!container) return;

  container.innerHTML = PORTAL_DATA.matrimonial.map(m => `
    <div class="portal-card" style="border-top: 4px solid var(--accent-gold);">
      <div class="portal-card-header">
        <span class="badge badge-amber">${m.type}</span>
        <span style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted);">${m.id}</span>
      </div>
      <h4 style="font-size: 1.12rem; font-weight: 800; color: var(--text-primary); margin-bottom: 4px;">
        ${m.edu} &bull; ${m.age}
      </h4>
      <p style="font-size: 0.86rem; color: var(--text-muted); margin-bottom: 10px;">தொழில் / பணி: <strong>${m.work}</strong> &bull; கிளை: <strong>${m.branch}</strong></p>
      
      <div style="background: var(--accent-gold-light); color: var(--accent-gold-hover); padding: 8px 12px; border-radius: var(--radius-xs); font-size: 0.86rem; font-weight: 700; margin-bottom: 12px;">
        ✨ ${m.sect}
      </div>

      <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 16px;">${m.details}</p>

      <div style="margin-top: auto;">
        <button onclick="alert('மணமகன்/மணமகள் எண்: ${m.id} விபரங்களுக்கு மாவட்ட திருமண வழிகாட்டல் ஒருங்கிணைப்பாளரை அணுகவும். எண்: +91 98424 00005')" class="btn-outline" style="width: 100%; justify-content: center; font-size: 0.84rem; padding: 9px 12px; border-color: var(--accent-gold); color: var(--accent-gold);">
          விபரங்கள் மற்றும் தொடர்பு கோர &rarr;
        </button>
      </div>
    </div>
  `).join('');
}

function renderFormsSection() {
  const container = document.getElementById('formsList');
  if (!container) return;

  container.innerHTML = PORTAL_DATA.forms.map(f => `
    <div class="portal-card" style="display: flex; flex-direction: row; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; margin-bottom: 14px;">
      <div style="display: flex; align-items: center; gap: 16px;">
        <div style="width: 48px; height: 48px; border-radius: var(--radius-sm); background: var(--primary-50); color: var(--primary-700); display: flex; align-items: center; justify-content: center; font-size: 1.6rem; flex-shrink: 0;">
          📄
        </div>
        <div>
          <h4 style="font-size: 1.05rem; font-weight: 800; color: var(--text-primary);">${f.title}</h4>
          <p style="font-size: 0.84rem; color: var(--text-muted); margin-top: 2px;">${f.desc}</p>
          <div style="font-size: 0.78rem; color: var(--primary-700); font-weight: 700; margin-top: 4px;">வடிவம்: ${f.format} &bull; அளவு: ${f.size}</div>
        </div>
      </div>
      <button onclick="simulateDownload('${f.title}')" class="btn-primary" style="font-size: 0.85rem; padding: 9px 18px;">
        ⬇️ தரவிறக்கம் செய்
      </button>
    </div>
  `).join('');
}

function simulateDownload(title) {
  alert(`📥 "${title}" வெற்றிகரமாக தரவிறக்கம் செய்யப்படுகிறது.`);
}

function renderEventsSection() {
  const container = document.getElementById('eventsList');
  if (!container) return;

  container.innerHTML = PORTAL_DATA.events.map(ev => `
    <div class="portal-card" style="margin-bottom: 20px; border-left: 6px solid var(--primary-700);">
      <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 8px;">
        <span class="badge badge-emerald" style="font-size: 0.86rem; padding: 4px 12px;">🗓️ ${ev.date} (${ev.day})</span>
        <span class="badge" style="background: var(--surface-alt); color: var(--text-secondary); font-size: 0.82rem;">📍 ${ev.location}</span>
      </div>
      <h3 style="font-size: 1.3rem; font-weight: 800; color: var(--primary-900); margin-bottom: 8px;">${ev.title}</h3>
      <p style="font-size: 0.94rem; color: var(--text-secondary); line-height: 1.6; margin-bottom: 12px;">${ev.desc}</p>
      <div style="font-size: 0.88rem; color: var(--primary-800); font-weight: 700;">
        🎤 சிறப்பு பேச்சாளர்: ${ev.speaker}
      </div>
    </div>
  `).join('');
}

function renderStatsSection() {
  const container = document.getElementById('statsTableBody');
  if (!container) return;

  container.innerHTML = PORTAL_DATA.monthlyStats.map(s => `
    <tr>
      <td style="font-weight: 800; color: var(--primary-900); font-size: 0.95rem;">${s.month}</td>
      <td style="font-weight: 700;">${s.dawahMeetings} கூட்டங்கள்</td>
      <td><span class="badge badge-red" style="font-size: 0.85rem;">${s.bloodUnits} யூனிட்</span></td>
      <td>${s.studentsMaktab} மாணவர்கள்</td>
      <td>${s.matrimonialSettled} நிக்காஹ்</td>
      <td style="font-weight: 800; color: var(--primary-800);">${s.welfareFunds}</td>
    </tr>
  `).join('');
}

function renderLinksSection() {
  const container = document.getElementById('officialLinksGrid');
  if (!container) return;

  container.innerHTML = PORTAL_DATA.officialLinks.map(l => `
    <a href="${l.url}" target="_blank" rel="noopener noreferrer" class="portal-card" style="text-decoration: none; color: inherit; border-top: 4px solid var(--primary-600);">
      <div class="portal-card-header">
        <h4 class="portal-card-title" style="color: var(--primary-900);">${l.title}</h4>
        <span style="font-size: 1.1rem; color: var(--primary-600);">↗</span>
      </div>
      <p class="portal-card-desc" style="margin-bottom: 14px;">${l.desc}</p>
      <span style="font-size: 0.84rem; color: var(--primary-700); font-weight: 700; margin-top: auto;">${l.url} &rarr;</span>
    </a>
  `).join('');
}
