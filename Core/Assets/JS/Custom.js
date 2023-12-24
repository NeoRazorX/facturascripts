/*
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
const Fsbootbox = (() => {
  const confirmAction = (
    viewName,
    action,
    title,
    message,
    cancel = "Cancelar",
    confirm = "Confirmar",
    modalId = false,
    customModalHTML = false
  ) => {
    const uniqueModalId = modalId || generateUniqueId();

    const modalOptions = {
      modalId: uniqueModalId,
      customModalHTML: customModalHTML,
      title: title,
      message: message,
      closeButton: false,
      buttons: {
        cancel: {
          label: `<i class="fas fa-times"></i> ${cancel}`,
        },
        confirm: {
          label: `<i class="fas fa-check"></i> ${confirm}`,
          className: "btn-danger",
        },
      },
      callback: (result) => {
        if (result) {
          $(`#form${viewName} :input[name="action"]`).val(action);
          $(`#form${viewName}`).submit();
        }
      },
    };

    showModal(modalOptions);
  };

  const showModal = (options) => {
    const modalContent = setModalHTML(options);

    $("body").append(modalContent);

    const $modal = $(`#${options.modalId}`);

    $modal.modal({
      backdrop: "static",
      keyboard: false,
    });

    $modal.find("#confirmButton").on("click", () => {
      options.callback(true);
      $modal.modal("hide");
    });

    $modal.on("hidden.bs.modal", () => {
      options.callback(false);
      $modal.remove();
    });
  };

  const setModalHTML = (options) => {
    return options.customModalHTML === false
      ? ModalBS(options)
      : generateModalHTML(options);
  };

  const generateModalHTML = (options) => {
    return options.customModalHTML;
  };

  const ModalBS = (options) => {
    return `
            <div class="modal fade" id="${options.modalId}" tabindex="-1" role="dialog" aria-labelledby="${options.modalId}Label" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="${options.modalId}Label">${options.title}</h5>                          
                        </div>
                        <div class="modal-body">
                            <p>${options.message}</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">${options.buttons.cancel.label}</button>
                            <button type="button" class="btn ${options.buttons.confirm.className}" id="confirmButton">
                                ${options.buttons.confirm.label}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
  };

  const generateUniqueId = () => {
    return `modal-${Date.now()}-${Math.floor(Math.random() * 1000)}`;
  };

  // métodos disponibles fuera del módulo
  return {
    confirmAction,
  };
})();

function animateSpinner(animation, result = "") {
  $(".btn-spin-action").each(function () {
    let btn = $(this);
    if (animation === "add") {
      btn.prop("disabled", true);
      let oldHtml = btn.children(".old-html");
      if (!oldHtml.length) {
        btn.html('<span class="old-html">' + btn.html() + "</span>");
      }
      if (!btn.children(".spinner").length) {
        btn.append(
          '<span class="spinner mx-auto" style="display: none;"><i class="fas fa-circle-notch fa-spin"></i></span>'
        );
        btn.find(".old-html").fadeOut(100, function () {
          btn.find(".spinner").fadeIn();
        });
      }
    }

    let spinner = btn.children(".spinner");

    if (spinner.data("animating")) {
      spinner.removeClass(spinner.data("animating")).data("animating", null);
      spinner.data("animationTimeout") &&
        clearTimeout(spinner.data("animationTimeout"));
    }

    spinner
      .addClass("spinner-" + animation)
      .data("animating", "spinner-" + animation);
    spinner.data(
      "animationTimeout",
      setTimeout(function () {
        if (animation === "remove") {
          btn.find(".spinner").fadeOut(100, function () {
            let attr = Boolean(btn.attr("load-after"));
            if (result !== "" && typeof attr !== "undefined" && attr === true) {
              let icon = "fas fa-times";
              if (result) {
                icon = "fas fa-check";
              }
              btn.append(
                '<div class="result mx-auto" style="display: none;"><i class="' +
                  icon +
                  '"></i></div>'
              );
            }

            let checkResult = btn.children(".result");
            if (checkResult.length) {
              btn.find(".result").fadeIn();
              setTimeout(function () {
                btn.find(".result").fadeOut(200, function () {
                  btn.find(".old-html").fadeIn();
                  spinner.remove();
                  btn.find(".result").remove();
                  btn.prop("disabled", false);
                });
              }, 500);
            } else {
              btn.find(".old-html").fadeIn();
              spinner.remove();
              btn.prop("disabled", false);
            }
          });
        }
      }, parseFloat(spinner.css("animation-duration")) * 1000)
    );
  });
}

function confirmAction(viewName, action, title, message, cancel, confirm) {
  Fsbootbox.confirmAction(viewName, action, title, message, cancel, confirm);
  return false;
}

function setModalParentForm(modal, form) {
  if (form.code) {
    // asignamos al formulario del modal el code del formulario donde sale el botón
    $("#" + modal)
      .parent()
      .find('input[name="code"]')
      .val(form.code.value);
  } else if (form.elements["code[]"]) {
    let codes = [];
    for (let num = 0; num < form.elements["code[]"].length; num++) {
      if (form.elements["code[]"][num].checked) {
        codes.push(form.elements["code[]"][num].value);
      }
    }
    // asignamos al formulario del modal los checkboxes marcados del formulario donde sale el botón
    $("#" + modal)
      .parent()
      .find('input[name="code"]')
      .val(codes.join());
  }
}

$(document).ready(function () {
  $(".clickableRow").mousedown(function (event) {
    if (event.which === 1 || event.which === 2) {
      var href = $(this).attr("data-href");
      var target = $(this).attr("data-target");
      if (typeof href !== typeof undefined && href !== false) {
        if (typeof target !== typeof undefined && target === "_blank") {
          window.open($(this).attr("data-href"));
        } else if (event.which === 2) {
          window.open($(this).attr("data-href"));
        } else {
          parent.document.location = $(this).attr("data-href");
        }
      }
    }
  });
  $(".cancelClickable").mousedown(function (event) {
    event.preventDefault();
    event.stopPropagation();
  });
  /* fix to dropdown submenus */
  $(document).on("click", "nav .dropdown-submenu", function (e) {
    e.stopPropagation();
  });
  $(document).on("shown.bs.modal", ".modal", function () {
    $(this).find("[autofocus]").focus();
  });
});
