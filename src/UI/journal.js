function saveEntry() {
  const content = document.getElementById('entry').value.trim();
  if (content !== '') {
    fetch('save_entry.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'content=' + encodeURIComponent(content)
    }).then(() => {
      document.getElementById('entry').value = '';
      loadEntries();
    });
  }
}

function loadEntries() {
  fetch('load_entries.php')
    .then(res => res.json())
    .then(data => {
      const container = document.getElementById('entries');
      container.innerHTML = '';
      data.reverse().forEach(entry => {
        const div = document.createElement('div');
        div.className = 'entry';
        div.innerHTML = `<small>${entry.created_at}</small><p>${entry.content}</p>`;
        container.appendChild(div);
      });
    });
}

window.onload = loadEntries;
