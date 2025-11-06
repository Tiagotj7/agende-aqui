// public/assets/app.js
document.addEventListener('DOMContentLoaded', function() {
  // elementos e modal bootstrap
  const modalEl = document.getElementById('eventModal');
  const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
  const form = document.getElementById('eventForm');
  const deleteBtn = document.getElementById('deleteBtn');

  const calendarEl = document.getElementById('calendar');
  const calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay' },
    selectable: true,
    select: function(info) {
      // abrir modal para criar evento
      document.getElementById('eventId').value = '';
      document.getElementById('title').value = '';
      document.getElementById('description').value = '';
      // preencher campos com datetimes (local)
      const s = isoLocalFromDate(info.start);
      const e = isoLocalFromDate(info.end ? info.end : info.start);
      document.getElementById('start').value = s;
      document.getElementById('end').value = e;
      deleteBtn.style.display = 'none';
      bsModal.show();
      calendar.unselect();
    },
    eventClick: function(info) {
      // abrir modal para editar evento
      fetch('api/events.php?single=' + info.event.id)
        .then(r => r.json())
        .then(data => {
          if (data.success && data.event) {
            const ev = data.event;
            document.getElementById('eventId').value = ev.id;
            document.getElementById('title').value = ev.title;
            document.getElementById('description').value = ev.description || '';
            document.getElementById('start').value = ev.start_datetime.replace(' ', 'T');
            document.getElementById('end').value = ev.end_datetime.replace(' ', 'T');
            deleteBtn.style.display = 'inline-block';
            bsModal.show();
          } else {
            alert('Erro ao carregar evento.');
          }
        });
    },
    events: 'api/events.php' // busca todos os eventos do usuário
  });

  calendar.render();

  // form submit (create/update)
  form.addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(form);
    fetch('api/save_event.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(res => {
        if (res.success) {
          calendar.refetchEvents();
          bsModal.hide();
        } else {
          alert(res.message || 'Erro ao salvar.');
        }
      }).catch(() => alert('Erro de rede.'));
  });

  // excluir
  deleteBtn.addEventListener('click', function() {
    if (!confirm('Deseja realmente excluir este evento?')) return;
    const id = document.getElementById('eventId').value;
    fetch('api/delete_event.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id })
    }).then(r => r.json())
      .then(res => {
        if (res.success) {
          calendar.refetchEvents();
          bsModal.hide();
        } else {
          alert(res.message || 'Erro ao excluir.');
        }
      });
  });

  // util: converte Date para input datetime-local local iso (sem timezone)
  function isoLocalFromDate(d) {
    const dt = new Date(d);
    dt.setMinutes(dt.getMinutes() - dt.getTimezoneOffset());
    return dt.toISOString().slice(0,16);
  }
});
