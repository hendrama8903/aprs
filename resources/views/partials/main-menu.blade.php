<ul class="sidebar-menu" data-widget="tree">
    {!! session('MenuHtml') !!}
</ul>
<script type="text/javascript">
    $(document).ready(function () {
        // Tandai menu aktif berdasarkan "/{controller}/Index" dari URL saat ini.
        var segments = window.location.pathname.split('/');
        var currentUrl = '/' + (segments[1] || 'Home') + '/Index';
        var $item = $('li[id="sidebarMenu' + currentUrl + '"]');
        var parentId = $item.attr('data-parentId');

        $item.addClass('active');
        if (parentId) {
            $('li[id="parentId' + parentId + '"]').addClass('active treeview menu open');
        }
    });
</script>
