$(document).ready(function() {
    // Mevcut dili belirle (TR/EN)
    var currentLang = $("#langValue").val() || "TR";

    // Statik SQL anahtar kelimeleri – istediğiniz öncelik sırasına göre:
    var sqlKeywords = [
        "CREATE DATABASE", "CREATE TABLE", "CREATE VIEW",
        "INSERT INTO", "UPDATE", "DELETE", "ALTER", "TRUNCATE",
        "DROP DATABASE", "DROP TABLE", "DROP VIEW",
        "SELECT", "FROM", "WHERE", "GROUP BY", "HAVING", "ORDER BY", "JOIN"
    ];
    var dynamicSuggestions = [];

    // Tooltip'leri başlat (sadece hover tetikleyicisi)
    $('[data-bs-toggle="tooltip"]').tooltip({
        html: true,
        delay: { show: 100, hide: 100 },
        trigger: 'hover'
    });

    // Tıklandığında tooltip'i gizle
    $(document).on("click", "[data-bs-toggle='tooltip']", function() {
        $(this).tooltip('hide');
    });

    // Sorgu durumu ve geçmişi
    var queryInProgress = false;
    var queryHistory = [];
    var historyIndex = -1;

    // Veritabanı listesini yenileme: aktif DB ve listeyi sunucudan al
    function refreshDatabaseList() {
        $.ajax({
            url: "/.hamu/db_actions.php?lang=" + currentLang,
            method: "POST",
            data: { refresh_db: true },
            dataType: "json"
        }).done(function(response) {
            var activeDb = response.active_db || "";
            var dbList = response.db_list || [];
            var newHtml = "";
            dbList.forEach(function(db) {
                var activeClass = (db === activeDb) ? "active-db" : "";
                newHtml += '<li class="db-name ' + activeClass + '" data-db="' + db + '" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-html="true" title="' + l_js__('tableloading') + '"><i class="fas fa-database"></i> ' + db + '</li>';
            });
            $("#databaseList.desktop-view").html(newHtml);

            if (window.innerWidth > 992) {
                $('[data-bs-toggle="tooltip"]').tooltip({
                    html: true,
                    delay: { show: 100, hide: 100 }
                });
            } else {
                $('[data-bs-toggle="tooltip"]').tooltip('dispose').removeAttr('data-bs-toggle title');
            }

            $(".db-name").hover(function() {
                var dbName = $(this).data("db");
                var elem = $(this);
                if (elem.attr("data-loaded") === "true") return;
                $.post("/.hamu/db_actions.php?lang=" + currentLang, { action: "tooltip", database: dbName }, function(data) {
                    elem.attr("data-bs-original-title", data)
                        .tooltip('_fixTitle')
                        .tooltip("show");
                    elem.attr("data-loaded", "true");
                }).fail(function() {
                    elem.attr("data-bs-original-title", l_js__('tableload_failed'))
                        .tooltip('_fixTitle')
                        .tooltip("show");
                });
            });
        }).fail(function() {
            console.error(l_js__('db_list_update_failed'));
        });
    }

    function updateDynamicSuggestions(callback) {
        var activeDb = $("#databaseList").attr("data-active-db") || "";
        if (activeDb !== "") {
            $.ajax({
                url: "/.hamu/db_actions.php?lang=" + currentLang,
                method: "POST",
                data: { action: "autocomplete", db: activeDb },
                dataType: "json",
                success: function(data) {
                    dynamicSuggestions = data;
                    if (callback) callback();
                },
                error: function() {
                    dynamicSuggestions = [];
                    if (callback) callback();
                }
            });
        } else {
            dynamicSuggestions = [];
            if (callback) callback();
        }
    }

    function getCombinedSuggestions() {
        return Array.from(new Set(sqlKeywords.concat(dynamicSuggestions)));
    }

    // Veritabanı adına tıklama: "USE db;" sorgusunu gönder (sonuç modalda gösterilsin)
    $(document).on("click", ".db-name", function() {
        var dbName = $(this).data("db");
        if (!dbName) return;
        submitQuery("USE " + dbName + ";", dbName);
    });

    // Sorgu gönderme fonksiyonu (Enter veya tıklama ile)
    function submitQuery(manualQuery, activeDbValue) {
        if (queryInProgress) return;
        var query = manualQuery || $("#sqlQuery").val().trim();
        if (!query) {
            $("#sqlQuery").focus();
            return;
        }
        $("#queryResult").html("<div class='spinner-border spinner-border-sm' role='status'><span class='visually-hidden'>" + l_js__('loading') + "</span></div> " + l_js__('run_query'));
        $("#resultModal").modal("show");
        $("#sqlQuery").prop("disabled", true);
        queryInProgress = true;

        $.post("/.hamu/db_actions.php?lang=" + currentLang, { sql: query, active_db: activeDbValue || "" }, function(data) {
            $("#queryResult").html(data);
            $("#sqlQuery").val("").prop("disabled", false);
            queryInProgress = false;
            if (queryHistory.length === 0 || queryHistory[queryHistory.length - 1] !== query) {
                queryHistory.push(query);
            }
            historyIndex = queryHistory.length;

            // İşlem sonrası uygun güncelleme: tüm veri değiştiren işlemlerden sonra liste ve öneriler güncellensin.
            if (/^\s*USE\s+/i.test(query) || /CREATE|DROP|REPLACE|UPDATE|INSERT|ALTER|TRUNCATE/i.test(query)) {
                refreshDatabaseList();
                updateDynamicSuggestions();
            }
        }).fail(function() {
            $("#queryResult").html(l_js__('error_occurred'));
            $("#sqlQuery").prop("disabled", false);
            queryInProgress = false;
        });
    }

    // #sqlQuery alanında Enter ile sorgu gönderme ve geçmişte gezinme
    $("#sqlQuery").on("keydown", function(e) {
        if (e.key === "Enter") {
            e.preventDefault();
            var queryText = $(this).val().trim();
            if (queryText === "") {
                $(this).focus();
            } else {
                $(this).autocomplete("close");
                setTimeout(function() {
                    submitQuery();
                }, 100);
            }
        } else if (e.shiftKey && e.key === "ArrowUp") {
            e.preventDefault();
            if (queryHistory.length > 0 && historyIndex > 0) {
                historyIndex--;
                $(this).val(queryHistory[historyIndex]);
            }
        } else if (e.shiftKey && e.key === "ArrowDown") {
            e.preventDefault();
            if (queryHistory.length > 0 && historyIndex < queryHistory.length - 1) {
                historyIndex++;
                $(this).val(queryHistory[historyIndex]);
            } else {
                historyIndex = queryHistory.length;
                $(this).val("");
            }
        }
    });

    // Global keydown: input, textarea, select veya modal dışında Enter'a basılırsa #sqlQuery'ye odaklan
    $(document).on("keydown", function(e) {
        if ($(e.target).is("input, textarea, select") || $(e.target).closest("#resultModal").length) {
            return;
        }
        if (e.key === "Enter") {
            $("#sqlQuery").focus();
            e.preventDefault();
        }
    });

    // Modal içerisindeyken Enter'a basılırsa modal'ı kapat
    $(document).on("keydown", function(e) {
        if (e.key === "Enter" && $(e.target).closest("#resultModal").length > 0) {
            e.preventDefault();
            var modalEl = document.getElementById("resultModal");
            var modalInstance = bootstrap.Modal.getInstance(modalEl);
            if (modalInstance) {
                modalInstance.hide();
            } else {
                new bootstrap.Modal(modalEl).hide();
            }
        }
    });

    // #sqlQuery'deki ardışık boşlukları tek boşluğa indirger
    $("#sqlQuery").on("input", function() {
        this.value = this.value.replace(/\s\s+/g, " ");
    });

    // Autocomplete'i başlat
    updateDynamicSuggestions(function() {
        $("#sqlQuery").autocomplete({
            source: function(request, response) {
                var term = request.term.split(/\s+/).pop();
                if (term === "") {
                    return response([]);
                }
                var combined = getCombinedSuggestions();
                response($.ui.autocomplete.filter(combined, term));
            },
            focus: function() { return false; },
            minLength: 0,
            autoFocus: true,
            delay: 100,
            position: { my: "left bottom", at: "left top-10", collision: "flip" },
            messages: {
                noResults: function() { return ""; },
                results: function(count) { return ""; }
            },
            select: function(event, ui) {
                event.preventDefault();
                var terms = this.value.split(/\s+/);
                terms.pop();
                terms.push(ui.item.value);
                this.value = terms.join(" ").replace(/\s+/g, " ").trim() + " ";
                $(this).autocomplete("close");
                return false;
            }
        }).on("keyup", function(e) {
            if (e.key === "ArrowUp" || e.key === "ArrowDown") {
                return;
            }
            var last = this.value.split(/\s+/).pop();
            if (last === "") {
                $(this).autocomplete("close");
            } else {
                $(this).autocomplete("search", last);
            }
        });
    });

    // Sayfa yüklendiğinde veritabanı listesini yenile
    refreshDatabaseList();
});
