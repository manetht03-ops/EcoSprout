// Shared client-side enhancements for login and list pagination.
document.addEventListener('DOMContentLoaded', function () {
  initLoginValidation();
  initFrontendPagination();
});

function initLoginValidation() {
  var form = document.getElementById('loginForm');
  if (!form) return;

  form.addEventListener('submit', function (e) {
    // Basic client-side validation (username is email-like).
    var u = document.getElementById('username').value.trim();
    var p = document.getElementById('password').value;
    if (u.length < 3 || p.length < 4) {
      e.preventDefault();
      alert('Please enter valid credentials (username and password).');
    }
  });
}

function initFrontendPagination() {
  var lists = document.querySelectorAll('.paginated-list');
  if (!lists.length) return;

  lists.forEach(function (listEl, index) {
    var items = Array.prototype.slice.call(listEl.querySelectorAll('.paginated-item'));
    if (items.length <= 1) return;

    var pageSize = parseInt(listEl.getAttribute('data-page-size') || '4', 10);
    if (!Number.isFinite(pageSize) || pageSize < 1) pageSize = 4;

    var totalPages = Math.max(1, Math.ceil(items.length / pageSize));
    var currentPage = 1;

    var controls = document.createElement('div');
    controls.className = 'pagination-controls';
    controls.setAttribute('data-pagination-id', String(index));

    var status = document.createElement('p');
    status.className = 'page-status';

    var buttonWrap = document.createElement('div');
    buttonWrap.className = 'page-buttons';

    var prevButton = document.createElement('button');
    prevButton.type = 'button';
    prevButton.textContent = 'Previous';

    var nextButton = document.createElement('button');
    nextButton.type = 'button';
    nextButton.textContent = 'Next';

    buttonWrap.appendChild(prevButton);
    buttonWrap.appendChild(nextButton);
    controls.appendChild(status);
    controls.appendChild(buttonWrap);

    listEl.insertAdjacentElement('afterend', controls);

    function renderPage() {
      var start = (currentPage - 1) * pageSize;
      var end = start + pageSize;

      items.forEach(function (item, itemIndex) {
        item.style.display = itemIndex >= start && itemIndex < end ? '' : 'none';
      });

      status.textContent = 'Page ' + currentPage + ' of ' + totalPages;
      prevButton.disabled = currentPage === 1;
      nextButton.disabled = currentPage === totalPages;
    }

    prevButton.addEventListener('click', function () {
      if (currentPage > 1) {
        currentPage -= 1;
        renderPage();
      }
    });

    nextButton.addEventListener('click', function () {
      if (currentPage < totalPages) {
        currentPage += 1;
        renderPage();
      }
    });

    renderPage();
  });
}