jQuery(document).ready(function ($) {
    $('#export-csv').on('click', function (e) {
      e.preventDefault();
  
      const from = $('input[name="from"]').val();
      const to = $('input[name="to"]').val();
  
      $.ajax({
        url: brags_export_ajax.ajax_url,
        method: 'POST',
        data: {
          action: 'export_transaction_report',
          from: from,
          to: to,
        },
        success: function (response) {
          const blob = new Blob([response], {
            type: 'text/csv;charset=utf-8;',
          });
          const link = document.createElement('a');
          link.href = URL.createObjectURL(blob);
          link.download = 'transaction_report.csv';
          document.body.appendChild(link);
          link.click();
          document.body.removeChild(link);
        },
        error: function () {
          alert('Failed to export CSV.');
        },
      });
    });
  });
  