/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017  Francesc Pineda Segarra  <francesc.pineda.segarra@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Customized style to have numbered lines in PHP Debug Bar's Database tab
 */
(function ($) {

    var csscls = PhpDebugBar.utils.makecsscls("phpdebugbar-widgets-");

    /**
     * Displays array element in a <ul> list
     *
     * Options:
     *  - data
     *  - itemRenderer: a function used to render list items (optional)
     */
    var ListWidget = PhpDebugBar.Widgets.ListWidget = PhpDebugBar.Widget.extend({

        tagName: "ol",

        className: csscls("list"),

        initialize: function (options) {
            if (!options["itemRenderer"]) {
                options["itemRenderer"] = this.itemRenderer;
            }
            this.set(options);
        },

        render: function () {
            this.bindAttr(["itemRenderer", "data"], function () {
                this.$el.empty();
                if (!this.has("data")) {
                    return;
                }

                var data = this.get("data");
                for (var i = 0; i < data.length; i++) {
                    var li = $("<li />").addClass(csscls("list-item")).appendTo(this.$el);
                    this.get("itemRenderer")(li, data[i]);
                }
            });
        },

        /**
         * Renders the content of a <li> element
         *
         * @param {jQuery} li The <li> element as a jQuery Object
         * @param {Object} value An item from the data array
         */
        itemRenderer: function (li, value) {
            li.html(renderValue(value));
        }

    });

    /**
     * Widget for the displaying sql queries
     *
     * Options:
     *  - data
     */
    var SQLQueriesWidget = PhpDebugBar.Widgets.SQLQueriesWidget = PhpDebugBar.Widget.extend({

        className: csscls("sqlqueries"),

        onFilterClick: function (el) {
            $(el).toggleClass(csscls("excluded"));

            var excludedLabels = [];
            this.$toolbar.find(csscls(".filter") + csscls(".excluded")).each(function () {
                excludedLabels.push(this.rel);
            });

            this.$list.$el.find("li[connection=" + $(el).attr("rel") + "]").toggle();

            this.set("exclude", excludedLabels);
        },

        render: function () {
            this.$status = $("<div />").addClass(csscls("status")).appendTo(this.$el);

            this.$toolbar = $("<div></div>").addClass(csscls("toolbar")).appendTo(this.$el);

            var filters = [], self = this;

            this.$list = new PhpDebugBar.Widgets.ListWidget({
                itemRenderer: function (li, stmt) {
                    $("<code />").addClass(csscls("sql")).html(PhpDebugBar.Widgets.highlight(stmt.sql, "sql")).appendTo(li);
                    if (stmt.duration_str) {
                        $("<span title=\"Duration\" />").addClass(csscls("duration")).text(stmt.duration_str).appendTo(li);
                    }
                    if (stmt.memory_str) {
                        $("<span title=\"Memory usage\" />").addClass(csscls("memory")).text(stmt.memory_str).appendTo(li);
                    }
                    if (typeof (stmt.row_count) != "undefined") {
                        $("<span title=\"Row count\" />").addClass(csscls("row-count")).text(stmt.row_count).appendTo(li);
                    }
                    if (typeof (stmt.stmt_id) != "undefined" && stmt.stmt_id) {
                        $("<span title=\"Prepared statement ID\" />").addClass(csscls("stmt-id")).text(stmt.stmt_id).appendTo(li);
                    }
                    if (stmt.connection) {
                        $("<span title=\"Connection\" />").addClass(csscls("database")).text(stmt.connection).appendTo(li);
                        li.attr("connection", stmt.connection);
                        if ($.inArray(stmt.connection, filters) == -1) {
                            filters.push(stmt.connection);
                            $("<a />")
                                .addClass(csscls("filter"))
                                .text(stmt.connection)
                                .attr("rel", stmt.connection)
                                .on("click", function () {
                                    self.onFilterClick(this);
                                })
                                .appendTo(self.$toolbar);
                            if (filters.length > 1) {
                                self.$toolbar.show();
                                self.$list.$el.css("margin-bottom", "20px");
                            }
                        }
                    }
                    if (typeof (stmt.is_success) != "undefined" && !stmt.is_success) {
                        li.addClass(csscls("error"));
                        li.append($("<span />").addClass(csscls("error")).text("[" + stmt.error_code + "] " + stmt.error_message));
                    }
                    if (stmt.params && !$.isEmptyObject(stmt.params)) {
                        var table = $("<table><tr><th colspan=\"2\">Params</th></tr></table>").addClass(csscls("params")).appendTo(li);
                        for (var key in stmt.params) {
                            if (typeof stmt.params[key] !== "function") {
                                table.append("<tr><td class=\"" + csscls("name") + "\">" + key + "</td><td class=\"" + csscls("value") +
                                    "\">" + stmt.params[key] + "</td></tr>");
                            }
                        }
                        li.css("cursor", "pointer").click(function () {
                            if (table.is(":visible")) {
                                table.hide();
                            } else {
                                table.show();
                            }
                        });
                    }
                }
            });
            this.$list.$el.appendTo(this.$el);

            this.bindAttr("data", function (data) {
                this.$list.set("data", data.statements);
                this.$status.empty();

                // Search for duplicate statements.
                for (var sql = {}, unique = 0, i = 0; i < data.statements.length; i++) {
                    var stmt = data.statements[i].sql;
                    if (data.statements[i].params && !$.isEmptyObject(data.statements[i].params)) {
                        stmt += " {" + $.param(data.statements[i].params, false) + "}";
                    }
                    sql[stmt] = sql[stmt] || {keys: []};
                    sql[stmt].keys.push(i);
                }
                // Add classes to all duplicate SQL statements.
                for (var stmt in sql) {
                    if (sql[stmt].keys.length > 1) {
                        unique++;
                        for (var i = 0; i < sql[stmt].keys.length; i++) {
                            this.$list.$el.find("." + csscls("list-item")).eq(sql[stmt].keys[i])
                                .addClass(csscls("sql-duplicate")).addClass(csscls("sql-duplicate-" + unique));
                        }
                    }
                }

                var t = $("<span />").text(data.nb_statements + " statements were executed").appendTo(this.$status);
                if (data.nb_failed_statements) {
                    t.append(", " + data.nb_failed_statements + " of which failed");
                }
                if (unique) {
                    t.append(", " + (data.nb_statements - unique) + " of which were duplicated");
                    t.append(", " + unique + " unique");
                }
                if (data.accumulated_duration_str) {
                    this.$status.append($("<span title=\"Accumulated duration\" />").addClass(csscls("duration")).text(data.accumulated_duration_str));
                }
                if (data.memory_usage_str) {
                    this.$status.append($("<span title=\"Memory usage\" />").addClass(csscls("memory")).text(data.memory_usage_str));
                }
            });
        }

    });

    /**
     * Widget for the displaying translations data
     *
     * Options:
     *  - data
     */
    var TranslationsWidget = PhpDebugBar.Widgets.TranslationsWidget = PhpDebugBar.Widget.extend({

        className: csscls('translations'),

        render: function () {
            this.$status = $('<div />').addClass(csscls('status')).appendTo(this.$el);

            this.$list = new PhpDebugBar.Widgets.ListWidget({
                itemRenderer: function (li, translation) {
                    var text = translation.key + " => " + translation.value;
                    if (translation.key == translation.value) {
                        var $line = $('<span/>').addClass(csscls('name')).addClass('text-danger').text(text);
                    } else {
                        var $line = $('<span/>').addClass(csscls('name')).addClass('text-muted').text(text);
                    }

                    $line.appendTo(li);
                }
            });
            this.$list.$el.appendTo(this.$el);

            this.bindAttr('data', function (data) {
                this.$list.set('data', data.translations);
                if(data.translations) {
                    var sentence = data.sentence || "translations were missed";
                    this.$status.empty().append($('<span />').text(data.translations.length + " " + sentence));
                }
            });
        }

    });

    /**
     * Widget for the displaying links
     *
     * Options:
     *  - data
     */
    var LinkIndicator = PhpDebugBar.DebugBar.Indicator.extend({

        tagName: "a",

        render: function () {
            LinkIndicator.__super__.render.apply(this);
            this.bindAttr("href", function (href) {
                this.$el.attr("href", href);
            });
        }

    });

    // Like:
    // phpdebugbar.addIndicator("Documentación", new LinkIndicator({ href: "https://www.facturascripts.com/documentacion", title: "Documentación" }));

})(PhpDebugBar.$);
