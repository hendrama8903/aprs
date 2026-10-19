{{-- Helper tampilan bersama halaman APR: badge status, persen kenaikan, format periode. --}}
<style>
    .apr-badge { display: inline-block; padding: 2px 10px; border-radius: 10px; font-size: 11px; font-weight: 600; letter-spacing: .3px; }
    .apr-badge.DRAFT { background: #EEF0F3; color: #4B5563; }
    .apr-badge.IN_APPROVAL { background: #FFF4DB; color: #9A6200; }
    .apr-badge.REJECTED { background: #FDECEA; color: #B42318; }
    .apr-badge.APPROVED { background: #E6F4EA; color: #17824F; }
    .apr-badge.ISSUED { background: #E7F1FB; color: #1C3FA6; }
    .apr-up { color: #B42318; }
    .apr-down { color: #17824F; }
</style>
<script type="text/javascript">
    var AprStatus = (function () {
        var labels = { DRAFT: "Draft", IN_APPROVAL: "In Approval", REJECTED: "Rejected", APPROVED: "Approved", ISSUED: "Issued" };

        function badge(status) {
            return $("<span>").addClass("apr-badge " + status).text(labels[status] || status);
        }

        function pct(value) {
            if (value === null || value === undefined) return $("<span>").text("-");
            var text = (value > 0 ? "+" : "") + Number(value).toFixed(2) + "%";
            return $("<span>").addClass(value > 0 ? "apr-up" : (value < 0 ? "apr-down" : "")).text(text);
        }

        function period(from, to) {
            if (!from || !to) return "";
            return moment(from).format("MMM YYYY") + " - " + moment(to).format("MMM YYYY");
        }

        return { labels: labels, badge: badge, pct: pct, period: period };
    })();
</script>
