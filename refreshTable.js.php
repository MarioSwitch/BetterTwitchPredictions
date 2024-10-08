<?php
echo "
<script>
    function refreshTable(id){
        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                document.getElementById('table').innerHTML = this.responseText;
            }
        };
        xhttp.open('GET', 'predictionTable.php?id=' + id, true);
        xhttp.send();
        setTimeout(refreshTable, 5000, id);
    }
</script>";