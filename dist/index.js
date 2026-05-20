var tt = Object.defineProperty;
var at = (e, s, r) => s in e ? tt(e, s, { enumerable: !0, configurable: !0, writable: !0, value: r }) : e[s] = r;
var be = (e, s, r) => at(e, typeof s != "symbol" ? s + "" : s, r);
import { jsxs as n, jsx as t, Fragment as J } from "react/jsx-runtime";
import { forwardRef as ne, useId as pe, useState as f, useRef as Y, useCallback as ae, useEffect as G } from "react";
import { router as $, Head as Ce, usePage as nt, Link as st } from "@inertiajs/react";
import { Upload as ze, File as ge, X as W, Check as Ne, Camera as rt, Search as lt, Plus as it, Image as Ee, FileText as de, FileArchive as ot, FileSpreadsheet as ct, History as dt, MessageSquare as mt, Trash2 as ve, Send as ut, Pencil as De, Download as pt, Clipboard as ht, ExternalLink as bt, Lock as ft, Unlock as gt, ChevronLeft as xt, Bug as Nt } from "lucide-react";
import { toast as v } from "sonner";
import { formatDistanceToNow as Ae } from "date-fns";
import { cs as $e } from "date-fns/locale";
function se(...e) {
  const s = [], r = (a) => {
    if (a) {
      if (Array.isArray(a)) {
        a.forEach(r);
        return;
      }
      s.push(String(a));
    }
  };
  return e.forEach(r), s.join(" ");
}
const vt = {
  default: "btn-primary",
  secondary: "btn-secondary",
  destructive: "btn-error",
  outline: "btn-outline",
  ghost: "btn-ghost",
  link: "btn-link",
  subtle: "btn-ghost bg-base-200 hover:bg-base-300"
}, yt = {
  default: "btn-md",
  sm: "btn-sm",
  xs: "btn-xs",
  lg: "btn-lg",
  icon: "btn-square btn-sm"
}, wt = ne(
  ({
    className: e,
    variant: s = "default",
    size: r = "default",
    fullWidth: a = !1,
    rounded: l = !1,
    loading: c = !1,
    leftSection: h,
    rightSection: p,
    children: x,
    disabled: d,
    ...o
  }, N) => /* @__PURE__ */ n(
    "button",
    {
      ref: N,
      disabled: d || c,
      className: se(
        "btn inline-flex items-center justify-center gap-2 whitespace-nowrap text-sm font-medium transition-colors focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50",
        vt[s],
        yt[r],
        a && "w-full",
        l && "rounded-full",
        e
      ),
      ...o,
      children: [
        c && /* @__PURE__ */ t("span", { className: "loading loading-spinner loading-sm" }),
        !c && h,
        x,
        p
      ]
    }
  )
);
wt.displayName = "Button";
const kt = ne(
  ({ className: e, label: s, description: r, error: a, indeterminate: l, id: c, ...h }, p) => {
    const x = pe(), d = c ?? x;
    return /* @__PURE__ */ n("div", { className: "flex flex-col gap-1", children: [
      /* @__PURE__ */ n(
        "label",
        {
          className: "flex cursor-pointer items-start gap-3",
          htmlFor: d,
          children: [
            /* @__PURE__ */ t(
              "input",
              {
                ref: (o) => {
                  typeof p == "function" ? p(o) : p && (p.current = o), o && (o.indeterminate = l ?? !1);
                },
                id: d,
                type: "checkbox",
                className: se(
                  "checkbox",
                  a && "checkbox-error",
                  e
                ),
                ...h
              }
            ),
            s && /* @__PURE__ */ n("span", { className: "text-sm font-medium", children: [
              s,
              r && /* @__PURE__ */ t("span", { className: "block font-normal text-base-content/70", children: r })
            ] })
          ]
        }
      ),
      a && /* @__PURE__ */ t("p", { className: "text-xs text-error", children: a })
    ] });
  }
);
kt.displayName = "Checkbox";
const Ie = ne(
  ({
    className: e,
    label: s,
    description: r,
    error: a,
    withAsterisk: l,
    leftSection: c,
    rightSection: h,
    id: p,
    ...x
  }, d) => {
    const o = pe(), N = p ?? o;
    return /* @__PURE__ */ n("div", { className: "flex w-full flex-col gap-1", children: [
      s && /* @__PURE__ */ n("label", { className: "text-sm font-medium", htmlFor: N, children: [
        s,
        l && /* @__PURE__ */ t("span", { className: "ml-1 text-error", children: "*" })
      ] }),
      r && /* @__PURE__ */ t("p", { className: "text-xs text-base-content/70", children: r }),
      /* @__PURE__ */ n("div", { className: "relative", children: [
        c && /* @__PURE__ */ t("div", { className: "pointer-events-none absolute inset-y-0 left-0 z-10 flex items-center pl-3", children: c }),
        /* @__PURE__ */ t(
          "input",
          {
            ref: d,
            id: N,
            className: se(
              "input w-full",
              a && "input-error",
              c && "pl-10",
              h && "pr-10",
              e
            ),
            ...x
          }
        ),
        h && /* @__PURE__ */ t("div", { className: "absolute inset-y-0 right-0 flex items-center pr-3", children: h })
      ] }),
      a && /* @__PURE__ */ t("p", { className: "text-xs text-error", children: a })
    ] });
  }
);
Ie.displayName = "Input";
const da = Ie, Tt = ne(
  ({
    className: e,
    label: s,
    description: r,
    error: a,
    withAsterisk: l,
    data: c,
    placeholder: h,
    id: p,
    ...x
  }, d) => {
    const o = c.map(
      (T) => typeof T == "string" ? { value: T, label: T } : T
    ), N = pe(), y = p ?? N;
    return /* @__PURE__ */ n("div", { className: "flex w-full flex-col gap-1", children: [
      s && /* @__PURE__ */ n("label", { className: "text-sm font-medium", htmlFor: y, children: [
        s,
        l && /* @__PURE__ */ t("span", { className: "ml-1 text-error", children: "*" })
      ] }),
      r && /* @__PURE__ */ t("p", { className: "text-xs text-base-content/70", children: r }),
      /* @__PURE__ */ n(
        "select",
        {
          ref: d,
          id: y,
          className: se(
            "select w-full",
            a && "select-error",
            e
          ),
          ...x,
          children: [
            h && /* @__PURE__ */ t("option", { value: "", disabled: !0, children: h }),
            o.map((T) => /* @__PURE__ */ t(
              "option",
              {
                value: T.value,
                disabled: T.disabled,
                children: T.label
              },
              T.value
            ))
          ]
        }
      ),
      a && /* @__PURE__ */ t("p", { className: "text-xs text-error", children: a })
    ] });
  }
);
Tt.displayName = "Select";
const St = ne(
  ({
    className: e,
    label: s,
    description: r,
    error: a,
    withAsterisk: l,
    minRows: c = 3,
    autosize: h,
    id: p,
    ...x
  }, d) => {
    const o = pe(), N = p ?? o;
    return /* @__PURE__ */ n("div", { className: "flex w-full flex-col gap-1", children: [
      s && /* @__PURE__ */ n("label", { className: "text-sm font-medium", htmlFor: N, children: [
        s,
        l && /* @__PURE__ */ t("span", { className: "ml-1 text-error", children: "*" })
      ] }),
      r && /* @__PURE__ */ t("p", { className: "text-xs text-base-content/70", children: r }),
      /* @__PURE__ */ t(
        "textarea",
        {
          ref: d,
          id: N,
          rows: c,
          className: se(
            "textarea w-full",
            a && "textarea-error",
            h && "resize-none",
            e
          ),
          ...x
        }
      ),
      a && /* @__PURE__ */ t("p", { className: "text-xs text-error", children: a })
    ] });
  }
);
St.displayName = "Textarea";
class je extends Error {
  constructor(r, a, l) {
    super(r);
    be(this, "status");
    be(this, "payload");
    this.name = "ApiError", this.status = a, this.payload = l;
  }
}
function _t() {
  if (typeof document > "u") return "";
  const e = document.cookie.split("; ").find((s) => s.startsWith("XSRF-TOKEN="));
  return e ? decodeURIComponent(e.split("=")[1] ?? "") : "";
}
function Le() {
  const e = _t();
  return e ? { "X-XSRF-TOKEN": e } : {};
}
let ee = null;
function Pe() {
  return ee || (ee = fetch("/sanctum/csrf-cookie", {
    method: "GET",
    credentials: "include",
    headers: { Accept: "application/json" }
  }).then(() => {
  }).catch(() => {
  }).finally(() => {
    ee = null;
  }), ee);
}
async function me(e, s, r) {
  return fetch(e, {
    ...s,
    headers: {
      ...r,
      ...Le(),
      ...s.headers
    },
    credentials: "include"
  });
}
const Ct = /* @__PURE__ */ new Set(["POST", "PUT", "PATCH", "DELETE"]);
function zt(e) {
  return Ct.has((e ?? "GET").toUpperCase());
}
function Oe(e, s) {
  if (e && typeof e == "object") {
    const r = e, a = r.errors;
    if (a && typeof a == "object") {
      const l = a, c = Object.keys(l)[0];
      if (c) {
        const h = l[c];
        if (Array.isArray(h) && h.length > 0)
          return String(h[0]);
      }
    }
    if (typeof r.message == "string" && r.message)
      return r.message;
  }
  return `HTTP ${s}`;
}
function ma(e) {
  return e instanceof DOMException && e.name === "AbortError";
}
async function te(e, s = {}) {
  const r = {
    "Content-Type": "application/json",
    Accept: "application/json"
  };
  let a = await me(e, s, r);
  if (a.status === 419 && zt(s.method) && (await Pe(), a = await me(e, s, r)), !a.ok) {
    const l = await a.json().catch(() => ({}));
    throw new je(
      Oe(l, a.status),
      a.status,
      l
    );
  }
  return a.status === 204 ? {} : a.json();
}
async function Et(e, s) {
  const r = {
    method: "POST",
    body: s
  }, a = { Accept: "application/json" };
  let l = await me(e, r, a);
  if (l.status === 419 && (await Pe(), l = await me(e, r, a)), !l.ok) {
    const c = await l.json().catch(() => ({}));
    throw new je(
      Oe(c, l.status),
      l.status,
      c
    );
  }
  return l.status === 204 ? {} : l.json();
}
const Dt = {
  get: (e, s) => te(e, { signal: s == null ? void 0 : s.signal }),
  post: (e, s, r) => te(e, {
    method: "POST",
    body: s ? JSON.stringify(s) : void 0,
    signal: r == null ? void 0 : r.signal
  }),
  put: (e, s, r) => te(e, {
    method: "PUT",
    body: s ? JSON.stringify(s) : void 0,
    signal: r == null ? void 0 : r.signal
  }),
  patch: (e, s, r) => te(e, {
    method: "PATCH",
    body: s ? JSON.stringify(s) : void 0,
    signal: r == null ? void 0 : r.signal
  }),
  delete: (e, s) => te(e, {
    method: "DELETE",
    signal: s == null ? void 0 : s.signal
  }),
  upload: Et
}, re = "cs-CZ", q = "—";
function le(e) {
  if (e == null || e === "")
    return null;
  const s = e instanceof Date ? e : new Date(e);
  return Number.isNaN(s.getTime()) ? null : s;
}
function ua(e, s = {}) {
  const r = le(e);
  return r ? new Intl.DateTimeFormat(re, {
    day: "2-digit",
    month: "2-digit",
    year: "numeric"
  }).format(r) : s.fallback ?? q;
}
function Fe(e, s = {}) {
  const r = le(e);
  return r ? new Intl.DateTimeFormat(re, {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit"
  }).format(r) : s.fallback ?? q;
}
function pa(e, s = {}) {
  const r = le(e);
  return r ? new Intl.DateTimeFormat(re, {
    day: "numeric",
    month: "long",
    year: "numeric"
  }).format(r) : s.fallback ?? q;
}
function ha(e, s = {}) {
  const r = le(e);
  return r ? new Intl.DateTimeFormat(re, {
    hour: "2-digit",
    minute: "2-digit"
  }).format(r) : s.fallback ?? q;
}
function ba(e, s = {}) {
  const r = le(e);
  if (!r) return s.fallback ?? q;
  const a = (r.getTime() - Date.now()) / 1e3, l = new Intl.RelativeTimeFormat(re, { numeric: "auto" }), c = [
    ["year", 31536e3],
    ["month", 2592e3],
    ["week", 604800],
    ["day", 86400],
    ["hour", 3600],
    ["minute", 60],
    ["second", 1]
  ];
  for (const [h, p] of c)
    if (Math.abs(a) >= p || h === "second")
      return l.format(Math.round(a / p), h);
  return s.fallback ?? q;
}
const ue = {
  open: "Otevřený",
  closed: "Zavřený"
}, K = {
  bug: "Chyba",
  feature: "Návrh",
  question: "Dotaz",
  other: "Jiné"
}, B = {
  low: "Nízká",
  medium: "Střední",
  high: "Vysoká",
  urgent: "Urgentní"
}, Me = {
  open: "badge-success",
  closed: "badge-ghost"
}, Ke = {
  low: "badge-info",
  medium: "badge-neutral",
  high: "badge-warning",
  urgent: "badge-error"
}, Be = {
  bug: "badge-error",
  feature: "badge-primary",
  question: "badge-info",
  other: "badge-ghost"
}, At = [
  "application/pdf",
  "image/jpeg",
  "image/png",
  "image/gif",
  "image/webp",
  "application/msword",
  "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
  "application/vnd.ms-excel",
  "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
  "text/plain",
  "application/rtf"
], $t = 20 * 1024 * 1024;
function It({
  endpoint: e,
  mode: s = "immediate",
  onFilesChange: r,
  onUploaded: a,
  onUploadSuccess: l,
  allowedMimes: c = At,
  maxSize: h = $t,
  maxFiles: p,
  showOcrCheckbox: x = !0,
  showMetadataInputs: d = !0,
  className: o,
  initialFiles: N,
  acceptedTypesLabel: y
}) {
  const [T, L] = f(() => N ?? []), [P, S] = f(!1), [u, b] = f([]), [z, I] = f(""), [C, Q] = f(""), [U, D] = f(!0), X = Y(null), F = Y([]);
  F.current = u;
  const R = Math.round(h / (1024 * 1024)), Z = u.length >= 2, ie = d && !Z, m = (i) => c.includes(i.type) ? i.size > h ? `Soubor je příliš velký (max ${R} MB)` : null : "Nepovolený typ souboru", A = async (i) => {
    const g = F.current[i];
    if (!g) return;
    const w = new FormData();
    w.append("file", g.file), x && w.append("run_ocr", U ? "1" : "0"), d && (g.title.trim() && w.append("title", g.title.trim()), g.description.trim() && w.append("description", g.description.trim()));
    try {
      b(
        (k) => k.map(
          (E, j) => j === i ? { ...E, status: "uploading", progress: 10 } : E
        )
      );
      const _ = await Dt.upload(e, w);
      b(
        (k) => k.map(
          (E, j) => j === i ? { ...E, status: "success", progress: 100 } : E
        )
      ), v.success(`Dokument "${g.file.name}" byl nahrán`), a == null || a(), l == null || l(_, g.file), setTimeout(() => {
        b((k) => k.filter((E, j) => j !== i));
      }, 2e3);
    } catch (_) {
      const k = _ instanceof Error ? _.message : "Neznámá chyba";
      b(
        (E) => E.map(
          (j, he) => he === i ? { ...j, status: "error", error: k } : j
        )
      ), v.error(`Upload "${g.file.name}" selhal: ${k}`);
    }
  }, O = (i, g, w) => {
    b(
      (_) => _.map((k, E) => E === i ? { ...k, [g]: w } : k)
    );
  }, H = () => {
    F.current.map((g, w) => ({ item: g, idx: w })).filter(({ item: g }) => g.status === "pending").forEach(({ idx: g }) => {
      A(g);
    });
  }, M = ae(
    (i) => {
      const g = Array.from(i), w = [];
      if (g.forEach((_) => {
        const k = m(_);
        if (k) {
          v.error(`${_.name}: ${k}`);
          return;
        }
        w.push(_);
      }), w.length !== 0) {
        if (s === "staged") {
          L((_) => {
            const k = [..._, ...w], E = typeof p == "number" ? k.slice(0, p) : k;
            return typeof p == "number" && k.length > p && v.error(`Maximální počet souborů: ${p}`), r == null || r(E), E;
          });
          return;
        }
        b((_) => {
          const k = _.length, j = k + w.length === 1, he = w.map((et, ye) => ({
            file: et,
            progress: 0,
            status: "pending",
            // Single mode: přebrat shared title/description z formuláře nahoře
            // Multi mode: každý soubor začne s prázdným per-file metadatem
            title: j && ye === 0 ? z : "",
            description: j && ye === 0 ? C : ""
          })), Qe = [..._, ...he];
          return j && setTimeout(() => A(k), 100), Qe;
        });
      }
    },
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [e, s, p, r, l, U, z, C, x, d]
  ), Xe = (i) => {
    i.preventDefault(), S(!0);
  }, Ze = (i) => {
    i.preventDefault(), S(!1);
  }, He = (i) => {
    i.preventDefault(), S(!1), i.dataTransfer.files.length > 0 && M(i.dataTransfer.files);
  }, Ve = () => {
    var i;
    (i = X.current) == null || i.click();
  }, Ye = (i) => {
    i.target.files && i.target.files.length > 0 && (M(i.target.files), i.target.value = "");
  }, Ge = (i) => {
    b((g) => g.filter((w, _) => _ !== i));
  }, We = (i) => {
    L((g) => {
      const w = g.filter((_, k) => k !== i);
      return r == null || r(w), w;
    });
  }, qe = c.join(","), Je = u.some((i) => i.status === "pending");
  return /* @__PURE__ */ n("div", { className: o, children: [
    ie && /* @__PURE__ */ n("div", { className: "grid grid-cols-1 md:grid-cols-2 gap-4 mb-4", children: [
      /* @__PURE__ */ n("div", { className: "flex flex-col gap-1", children: [
        /* @__PURE__ */ t("label", { className: "label py-1 block", children: /* @__PURE__ */ t("span", { className: "font-medium text-xs", children: "Název dokumentu (volitelné)" }) }),
        /* @__PURE__ */ t(
          "input",
          {
            type: "text",
            className: "input input-bordered input-sm w-full",
            placeholder: "Automaticky z názvu souboru",
            value: z,
            onChange: (i) => I(i.target.value)
          }
        )
      ] }),
      /* @__PURE__ */ n("div", { className: "flex flex-col gap-1", children: [
        /* @__PURE__ */ t("label", { className: "label py-1 block", children: /* @__PURE__ */ t("span", { className: "font-medium text-xs", children: "Popis (volitelné)" }) }),
        /* @__PURE__ */ t(
          "input",
          {
            type: "text",
            className: "input input-bordered input-sm w-full",
            placeholder: "Krátký popis dokumentu",
            value: C,
            onChange: (i) => Q(i.target.value)
          }
        )
      ] })
    ] }),
    x && /* @__PURE__ */ t("div", { className: "flex flex-col gap-1 mb-4", children: /* @__PURE__ */ n("label", { className: "label cursor-pointer justify-start gap-2", children: [
      /* @__PURE__ */ t(
        "input",
        {
          type: "checkbox",
          className: "checkbox checkbox-sm checkbox-primary",
          checked: U,
          onChange: (i) => D(i.target.checked)
        }
      ),
      /* @__PURE__ */ t("span", { className: "text-sm font-medium", children: "Spustit OCR rozpoznání textu" })
    ] }) }),
    /* @__PURE__ */ n(
      "div",
      {
        className: `border-2 border-dashed rounded-lg p-8 text-center cursor-pointer transition-colors ${P ? "border-primary bg-primary/10" : "border-base-300 hover:border-primary/50 hover:bg-base-200"}`,
        onDragOver: Xe,
        onDragLeave: Ze,
        onDrop: He,
        onClick: Ve,
        children: [
          /* @__PURE__ */ t(
            "input",
            {
              ref: X,
              type: "file",
              className: "hidden",
              multiple: !0,
              accept: qe,
              onChange: Ye
            }
          ),
          /* @__PURE__ */ t(
            ze,
            {
              size: 48,
              className: `mx-auto mb-3 ${P ? "text-primary" : "text-base-content/30"}`
            }
          ),
          /* @__PURE__ */ n("p", { className: "text-base-content/70", children: [
            "Přetáhněte soubory sem nebo ",
            /* @__PURE__ */ t("span", { className: "text-primary", children: "klikněte pro výběr" })
          ] }),
          /* @__PURE__ */ n("p", { className: "text-xs text-base-content/50 mt-2", children: [
            y ?? "PDF, obrázky, Word, Excel, TXT",
            " (max ",
            R,
            " MB)"
          ] })
        ]
      }
    ),
    Z && Je && /* @__PURE__ */ n("div", { className: "mt-3 flex items-center justify-between", children: [
      /* @__PURE__ */ t("p", { className: "text-xs text-base-content/60", children: "Vyplňte volitelné metadata u souborů a klikněte na Nahrát." }),
      /* @__PURE__ */ n(
        "button",
        {
          type: "button",
          className: "btn btn-primary btn-sm",
          onClick: H,
          children: [
            "Nahrát ",
            u.filter((i) => i.status === "pending").length,
            " souborů"
          ]
        }
      )
    ] }),
    s === "staged" && T.length > 0 && /* @__PURE__ */ t("ul", { className: "mt-4 space-y-2", children: T.map((i, g) => /* @__PURE__ */ n(
      "li",
      {
        className: "flex items-center gap-3 p-3 rounded-lg bg-base-200",
        children: [
          /* @__PURE__ */ t(ge, { size: 20, className: "text-base-content/50 shrink-0" }),
          /* @__PURE__ */ n("div", { className: "flex-1 min-w-0", children: [
            /* @__PURE__ */ t("div", { className: "text-sm font-medium truncate", children: i.name }),
            /* @__PURE__ */ n("div", { className: "text-xs text-base-content/50", children: [
              (i.size / 1024).toFixed(1),
              " KB"
            ] })
          ] }),
          /* @__PURE__ */ t(
            "button",
            {
              type: "button",
              className: "btn btn-ghost btn-xs btn-circle",
              onClick: () => We(g),
              title: "Odebrat",
              "aria-label": "Odebrat",
              children: /* @__PURE__ */ t(W, { size: 14 })
            }
          )
        ]
      },
      `${i.name}-${g}`
    )) }),
    u.length > 0 && /* @__PURE__ */ t("ul", { className: "mt-4 space-y-2", children: u.map((i, g) => /* @__PURE__ */ n(
      "li",
      {
        className: `flex flex-col gap-2 p-3 rounded-lg ${i.status === "error" ? "bg-error/10" : i.status === "success" ? "bg-success/10" : "bg-base-200"}`,
        children: [
          /* @__PURE__ */ n("div", { className: "flex items-center gap-3", children: [
            /* @__PURE__ */ t(ge, { size: 20, className: "text-base-content/50 shrink-0" }),
            /* @__PURE__ */ n("div", { className: "flex-1 min-w-0", children: [
              /* @__PURE__ */ t("div", { className: "text-sm font-medium truncate", children: i.file.name }),
              /* @__PURE__ */ n("div", { className: "text-xs text-base-content/50", children: [
                (i.file.size / 1024).toFixed(1),
                " KB",
                i.error && /* @__PURE__ */ t("span", { className: "text-error ml-2", children: i.error })
              ] }),
              i.status === "uploading" && /* @__PURE__ */ t(
                "progress",
                {
                  className: "progress progress-primary w-full h-1 mt-1",
                  value: i.progress,
                  max: "100"
                }
              )
            ] }),
            i.status === "uploading" && /* @__PURE__ */ t("span", { className: "loading loading-spinner loading-sm text-primary" }),
            i.status === "success" && /* @__PURE__ */ t(Ne, { size: 20, className: "text-success" }),
            (i.status === "error" || i.status === "pending") && /* @__PURE__ */ t(
              "button",
              {
                type: "button",
                className: "btn btn-ghost btn-xs btn-circle",
                onClick: () => Ge(g),
                title: "Odebrat",
                "aria-label": "Odebrat",
                children: /* @__PURE__ */ t(W, { size: 14 })
              }
            )
          ] }),
          Z && d && /* @__PURE__ */ n("div", { className: "grid grid-cols-1 sm:grid-cols-2 gap-2 pl-8", children: [
            /* @__PURE__ */ t(
              "input",
              {
                type: "text",
                className: "input input-bordered input-xs",
                placeholder: "Název (volitelné)",
                value: i.title,
                onChange: (w) => O(g, "title", w.target.value),
                disabled: i.status === "uploading" || i.status === "success"
              }
            ),
            /* @__PURE__ */ t(
              "input",
              {
                type: "text",
                className: "input input-bordered input-xs",
                placeholder: "Popis (volitelné)",
                value: i.description,
                onChange: (w) => O(g, "description", w.target.value),
                disabled: i.status === "uploading" || i.status === "success"
              }
            )
          ] })
        ]
      },
      g
    )) })
  ] });
}
const we = 10;
function ke(e) {
  const s = Math.min(e.startX, e.endX), r = Math.min(e.startY, e.endY), a = Math.abs(e.endX - e.startX), l = Math.abs(e.endY - e.startY);
  return { x: s, y: r, width: a, height: l };
}
function jt({ onCapture: e, onCancel: s, maxSize: r }) {
  const a = Y(null), l = Y(null), [c, h] = f(null), [p, x] = f(!1), [d, o] = f(!1), [N, y] = f(null);
  G(() => {
    const u = (b) => {
      b.key === "Escape" && s();
    };
    return window.addEventListener("keydown", u), () => window.removeEventListener("keydown", u);
  }, [s]);
  const T = ae((u) => {
    var C;
    if (d || N || u.button !== 0)
      return;
    const b = (C = a.current) == null ? void 0 : C.getBoundingClientRect();
    if (!b)
      return;
    const z = u.clientX - b.left, I = u.clientY - b.top;
    h({ startX: z, startY: I, endX: z, endY: I }), x(!0);
  }, [d, N]), L = ae((u) => {
    var C;
    if (!p || !c)
      return;
    const b = (C = a.current) == null ? void 0 : C.getBoundingClientRect();
    if (!b)
      return;
    const z = u.clientX - b.left, I = u.clientY - b.top;
    h({ ...c, endX: z, endY: I });
  }, [p, c]), P = ae(async () => {
    if (!p || !c)
      return;
    x(!1);
    const u = ke(c);
    if (u.width < we || u.height < we) {
      h(null);
      return;
    }
    o(!0);
    try {
      const { default: b } = await import("html2canvas-pro"), z = window.devicePixelRatio || 1, I = a.current, C = l.current, Q = (D) => !!(I && (D === I || I.contains(D)) || C && (D === C || C.contains(D)));
      (await b(document.body, {
        x: window.scrollX + u.x,
        y: window.scrollY + u.y,
        width: u.width,
        height: u.height,
        scale: z,
        useCORS: !0,
        logging: !1,
        ignoreElements: Q
      })).toBlob((D) => {
        if (!D) {
          y("Nepodařilo se vytvořit obrázek");
          return;
        }
        if (r && D.size > r) {
          const R = Math.round(r / 1024 / 1024);
          y(`Screenshot je příliš velký (max ${R} MB).`);
          return;
        }
        const F = `screenshot-${(/* @__PURE__ */ new Date()).toISOString().replace(/[:.]/g, "-")}.png`;
        e(new File([D], F, { type: "image/png" }));
      }, "image/png");
    } catch (b) {
      const z = b instanceof Error ? b.message : "Neznámá chyba";
      y(`Pořízení screenshotu selhalo: ${z}`);
    }
  }, [p, c, e, r]);
  if (N)
    return /* @__PURE__ */ t("div", { className: "fixed inset-0 z-[70] flex items-center justify-center bg-base-content/60", children: /* @__PURE__ */ t("div", { role: "alert", className: "alert alert-error max-w-md", children: /* @__PURE__ */ n("div", { className: "flex flex-col gap-3", children: [
      /* @__PURE__ */ t("span", { children: N }),
      /* @__PURE__ */ t("button", { type: "button", className: "btn btn-sm", onClick: s, children: "Zavřít" })
    ] }) }) });
  const S = c ? ke(c) : null;
  return /* @__PURE__ */ n(J, { children: [
    /* @__PURE__ */ n(
      "div",
      {
        ref: a,
        className: "fixed inset-0 z-[60] cursor-crosshair select-none overflow-hidden bg-base-content/40",
        onMouseDown: T,
        onMouseMove: L,
        onMouseUp: P,
        onMouseLeave: P,
        children: [
          p && S && S.width > 0 && S.height > 0 && /* @__PURE__ */ t(
            "div",
            {
              className: "pointer-events-none absolute border-2 border-dashed border-primary bg-primary/10",
              style: {
                left: `${S.x}px`,
                top: `${S.y}px`,
                width: `${S.width}px`,
                height: `${S.height}px`
              }
            }
          ),
          /* @__PURE__ */ t("div", { className: "pointer-events-none absolute left-1/2 top-4 -translate-x-1/2 rounded-full bg-base-content/60 px-3 py-1.5 text-sm text-base-100", children: "Vyberte oblast pro screenshot" }),
          /* @__PURE__ */ t(
            "button",
            {
              type: "button",
              className: "btn btn-sm btn-ghost absolute right-4 top-4 text-base-100 hover:bg-base-content/20",
              onClick: (u) => {
                u.stopPropagation(), s();
              },
              onMouseDown: (u) => u.stopPropagation(),
              children: "Zrušit"
            }
          )
        ]
      }
    ),
    d && /* @__PURE__ */ t(
      "div",
      {
        ref: l,
        className: "fixed inset-0 z-[70] flex items-center justify-center bg-base-content/60",
        children: /* @__PURE__ */ t("span", { className: "loading loading-spinner loading-lg text-primary" })
      }
    )
  ] });
}
const V = 20, Te = 10 * 1024 * 1024, Lt = [
  "image/jpeg",
  "image/png",
  "image/webp",
  "image/gif",
  "application/pdf",
  "text/plain",
  "text/csv",
  "application/csv",
  "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
  "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
  "application/zip",
  "application/x-zip-compressed"
], Se = {
  title: "",
  description: "",
  category: "bug",
  priority: "medium"
};
function xe({ open: e, onClose: s, onCreated: r }) {
  const [a, l] = f({ ...Se }), [c, h] = f([]), [p, x] = f(!1), [d, o] = f({}), [N, y] = f(!1), [T, L] = f(0);
  if (G(() => {
    e && (l({ ...Se }), h([]), o({}), y(!1), L((u) => u + 1));
  }, [e]), !e) return null;
  const P = (u) => {
    u.preventDefault(), x(!0), o({}), $.post(
      "/tickets",
      {
        title: a.title,
        description: a.description,
        category: a.category,
        priority: a.priority,
        // Auto-fill kontextu — page_url, viewport, user_agent z window
        page_url: window.location.pathname + window.location.search,
        viewport: `${window.innerWidth}x${window.innerHeight}`,
        user_agent: navigator.userAgent,
        attachments: c
      },
      {
        forceFormData: !0,
        onSuccess: () => {
          v.success("Ticket byl úspěšně vytvořen"), r == null || r(), s();
        },
        onError: (b) => {
          o(b), v.error("Zkontrolujte vyplněná pole");
        },
        onFinish: () => x(!1)
      }
    );
  }, S = (u) => {
    h((b) => b.length >= V ? (v.error(`Maximum ${V} příloh — odeberte některou před přidáním další.`), b) : [...b, u]), L((b) => b + 1), y(!1), v.success("Screenshot přidán mezi přílohy");
  };
  return /* @__PURE__ */ n(J, { children: [
    /* @__PURE__ */ n(
      "div",
      {
        className: "modal modal-open",
        style: N ? { display: "none" } : void 0,
        children: [
          /* @__PURE__ */ n("div", { className: "modal-box max-w-2xl", children: [
            /* @__PURE__ */ t("h3", { className: "font-bold text-lg mb-4", children: "Nový ticket" }),
            /* @__PURE__ */ n("form", { onSubmit: P, className: "space-y-4", children: [
              /* @__PURE__ */ n("div", { className: "flex flex-col gap-1", children: [
                /* @__PURE__ */ t("label", { className: "label py-1 block", children: /* @__PURE__ */ n("span", { className: "text-sm font-medium", children: [
                  "Název ",
                  /* @__PURE__ */ t("span", { className: "text-error", children: "*" })
                ] }) }),
                /* @__PURE__ */ t(
                  "input",
                  {
                    type: "text",
                    className: `input input-bordered w-full ${d.title ? "input-error" : ""}`,
                    placeholder: "Krátký popis problému",
                    value: a.title,
                    onChange: (u) => l({ ...a, title: u.target.value }),
                    required: !0,
                    maxLength: 255,
                    autoFocus: !0
                  }
                ),
                d.title && /* @__PURE__ */ t("label", { className: "label", children: /* @__PURE__ */ t("span", { className: "text-xs text-error", children: d.title }) })
              ] }),
              /* @__PURE__ */ n("div", { className: "grid grid-cols-1 md:grid-cols-2 gap-4", children: [
                /* @__PURE__ */ n("div", { className: "flex flex-col gap-1", children: [
                  /* @__PURE__ */ t("label", { className: "label py-1 block", children: /* @__PURE__ */ t("span", { className: "text-sm font-medium", children: "Kategorie" }) }),
                  /* @__PURE__ */ t(
                    "select",
                    {
                      className: "select select-bordered w-full",
                      value: a.category,
                      onChange: (u) => l({ ...a, category: u.target.value }),
                      children: Object.keys(K).map((u) => /* @__PURE__ */ t("option", { value: u, children: K[u] }, u))
                    }
                  )
                ] }),
                /* @__PURE__ */ n("div", { className: "flex flex-col gap-1", children: [
                  /* @__PURE__ */ t("label", { className: "label py-1 block", children: /* @__PURE__ */ t("span", { className: "text-sm font-medium", children: "Priorita" }) }),
                  /* @__PURE__ */ t(
                    "select",
                    {
                      className: "select select-bordered w-full",
                      value: a.priority,
                      onChange: (u) => l({ ...a, priority: u.target.value }),
                      children: Object.keys(B).map((u) => /* @__PURE__ */ t("option", { value: u, children: B[u] }, u))
                    }
                  )
                ] })
              ] }),
              /* @__PURE__ */ n("div", { className: "flex flex-col gap-1", children: [
                /* @__PURE__ */ t("label", { className: "label py-1 block", children: /* @__PURE__ */ n("span", { className: "text-sm font-medium", children: [
                  "Popis ",
                  /* @__PURE__ */ t("span", { className: "text-error", children: "*" })
                ] }) }),
                /* @__PURE__ */ t(
                  "textarea",
                  {
                    className: `textarea textarea-bordered w-full min-h-32 ${d.description ? "textarea-error" : ""}`,
                    placeholder: "Co se stalo? Co očekáváte? Krok za krokem reprodukce…",
                    value: a.description,
                    onChange: (u) => l({ ...a, description: u.target.value }),
                    required: !0,
                    maxLength: 1e4
                  }
                ),
                d.description && /* @__PURE__ */ t("label", { className: "label", children: /* @__PURE__ */ t("span", { className: "text-xs text-error", children: d.description }) })
              ] }),
              /* @__PURE__ */ n("div", { className: "flex flex-col gap-1", children: [
                /* @__PURE__ */ t("label", { className: "label py-1 block", children: /* @__PURE__ */ t("span", { className: "text-sm font-medium", children: "Screenshot stránky" }) }),
                /* @__PURE__ */ n(
                  "button",
                  {
                    type: "button",
                    className: "btn btn-sm btn-outline gap-1 self-start",
                    onClick: () => y(!0),
                    disabled: c.length >= V,
                    title: c.length >= V ? `Maximum ${V} příloh` : "Pořídit screenshot stránky a přidat do příloh",
                    children: [
                      /* @__PURE__ */ t(rt, { size: 14 }),
                      "Udělat screenshot"
                    ]
                  }
                )
              ] }),
              /* @__PURE__ */ n("div", { className: "flex flex-col gap-1", children: [
                /* @__PURE__ */ t("label", { className: "label py-1 block", children: /* @__PURE__ */ t("span", { className: "text-sm font-medium", children: "Přílohy (volitelné)" }) }),
                /* @__PURE__ */ t(
                  It,
                  {
                    mode: "staged",
                    allowedMimes: Lt,
                    acceptedTypesLabel: "Obrázky, PDF, TXT/LOG/CSV, DOCX/XLSX, ZIP",
                    maxSize: Te,
                    maxFiles: V,
                    showOcrCheckbox: !1,
                    showMetadataInputs: !1,
                    initialFiles: c,
                    onFilesChange: h
                  },
                  T
                ),
                d.attachments && /* @__PURE__ */ t("label", { className: "label", children: /* @__PURE__ */ t("span", { className: "text-xs text-error", children: d.attachments }) })
              ] }),
              /* @__PURE__ */ n("div", { className: "modal-action", children: [
                /* @__PURE__ */ t(
                  "button",
                  {
                    type: "button",
                    className: "btn btn-ghost",
                    onClick: s,
                    disabled: p,
                    children: "Zrušit"
                  }
                ),
                /* @__PURE__ */ n(
                  "button",
                  {
                    type: "submit",
                    className: "btn btn-primary",
                    disabled: p,
                    children: [
                      p && /* @__PURE__ */ t("span", { className: "loading loading-spinner loading-sm" }),
                      "Vytvořit"
                    ]
                  }
                )
              ] })
            ] })
          ] }),
          /* @__PURE__ */ t(
            "button",
            {
              type: "button",
              className: "modal-backdrop",
              onClick: s,
              "aria-label": "Zavřít modal",
              children: "close"
            }
          )
        ]
      }
    ),
    N && /* @__PURE__ */ t(
      jt,
      {
        onCapture: S,
        onCancel: () => y(!1),
        maxSize: Te
      }
    )
  ] });
}
function Pt({ filters: e, onChange: s, canViewAllOrgs: r }) {
  return /* @__PURE__ */ t("div", { className: "card bg-base-100 shadow-sm", children: /* @__PURE__ */ n("div", { className: "card-body p-4", children: [
    /* @__PURE__ */ n("div", { className: "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3", children: [
      /* @__PURE__ */ n("div", { className: "flex flex-col gap-1 lg:col-span-2", children: [
        /* @__PURE__ */ t("label", { className: "label py-1 block", children: /* @__PURE__ */ t("span", { className: "font-medium text-xs", children: "Hledat" }) }),
        /* @__PURE__ */ n("div", { className: "join w-full", children: [
          /* @__PURE__ */ t(
            "span",
            {
              className: "join-item btn btn-square btn-sm bg-base-200 pointer-events-none",
              "aria-hidden": "true",
              children: /* @__PURE__ */ t(lt, { size: 16 })
            }
          ),
          /* @__PURE__ */ t(
            "input",
            {
              type: "text",
              className: "input input-bordered input-sm join-item w-full",
              placeholder: "Hledej v názvu nebo popisu…",
              value: e.search ?? "",
              onChange: (a) => s({ ...e, search: a.target.value || void 0 })
            }
          )
        ] })
      ] }),
      /* @__PURE__ */ n("div", { className: "flex flex-col gap-1", children: [
        /* @__PURE__ */ t("label", { className: "label py-1 block", children: /* @__PURE__ */ t("span", { className: "font-medium text-xs", children: "Stav" }) }),
        /* @__PURE__ */ n(
          "select",
          {
            className: "select select-bordered select-sm w-full",
            value: e.status ?? "",
            onChange: (a) => s({
              ...e,
              status: a.target.value || void 0
            }),
            children: [
              /* @__PURE__ */ t("option", { value: "", children: "Vše" }),
              Object.keys(ue).map((a) => /* @__PURE__ */ t("option", { value: a, children: ue[a] }, a))
            ]
          }
        )
      ] }),
      /* @__PURE__ */ n("div", { className: "flex flex-col gap-1", children: [
        /* @__PURE__ */ t("label", { className: "label py-1 block", children: /* @__PURE__ */ t("span", { className: "font-medium text-xs", children: "Kategorie" }) }),
        /* @__PURE__ */ n(
          "select",
          {
            className: "select select-bordered select-sm w-full",
            value: e.category ?? "",
            onChange: (a) => s({
              ...e,
              category: a.target.value || void 0
            }),
            children: [
              /* @__PURE__ */ t("option", { value: "", children: "Vše" }),
              Object.keys(K).map((a) => /* @__PURE__ */ t("option", { value: a, children: K[a] }, a))
            ]
          }
        )
      ] }),
      /* @__PURE__ */ n("div", { className: "flex flex-col gap-1", children: [
        /* @__PURE__ */ t("label", { className: "label py-1 block", children: /* @__PURE__ */ t("span", { className: "font-medium text-xs", children: "Priorita" }) }),
        /* @__PURE__ */ n(
          "select",
          {
            className: "select select-bordered select-sm w-full",
            value: e.priority ?? "",
            onChange: (a) => s({
              ...e,
              priority: a.target.value || void 0
            }),
            children: [
              /* @__PURE__ */ t("option", { value: "", children: "Vše" }),
              Object.keys(B).map((a) => /* @__PURE__ */ t("option", { value: a, children: B[a] }, a))
            ]
          }
        )
      ] })
    ] }),
    r && /* @__PURE__ */ t("div", { className: "flex flex-col gap-1 mt-2", children: /* @__PURE__ */ n("label", { className: "label cursor-pointer justify-start gap-2", children: [
      /* @__PURE__ */ t(
        "input",
        {
          type: "checkbox",
          className: "checkbox checkbox-sm checkbox-primary",
          checked: !!e.all_orgs,
          onChange: (a) => s({ ...e, all_orgs: a.target.checked || void 0 })
        }
      ),
      /* @__PURE__ */ t("span", { className: "text-sm font-medium", children: "Všechny organizace (superadmin)" })
    ] }) })
  ] }) });
}
const Ot = (e) => Fe(e);
function Ft({ ticket: e }) {
  var r, a;
  return /* @__PURE__ */ n("tr", { className: "hover:bg-base-200 cursor-pointer", onClick: () => {
    $.visit(`/tickets/${e.uuid}`);
  }, children: [
    /* @__PURE__ */ t("td", { children: /* @__PURE__ */ t("span", { className: `badge ${Me[e.status]} badge-sm`, children: ue[e.status] }) }),
    /* @__PURE__ */ t("td", { children: /* @__PURE__ */ t("span", { className: `badge ${Be[e.category]} badge-sm`, children: K[e.category] }) }),
    /* @__PURE__ */ t("td", { children: /* @__PURE__ */ t("span", { className: `badge ${Ke[e.priority]} badge-sm`, children: B[e.priority] }) }),
    /* @__PURE__ */ t("td", { className: "font-medium", children: e.title }),
    /* @__PURE__ */ t("td", { className: "text-sm text-base-content/70", children: ((r = e.creator) == null ? void 0 : r.name) ?? "—" }),
    /* @__PURE__ */ t("td", { className: "text-sm text-base-content/70", children: Ot(e.created_at) }),
    /* @__PURE__ */ t("td", { className: "text-sm text-base-content/70", children: ((a = e.attachments) == null ? void 0 : a.length) > 0 ? `${e.attachments.length}× příloha` : "—" })
  ] });
}
function Mt({ tickets: e }) {
  return e.length === 0 ? /* @__PURE__ */ t("div", { className: "alert", children: /* @__PURE__ */ t("span", { children: "Žádné tickety neodpovídají filtrům." }) }) : /* @__PURE__ */ t("div", { className: "overflow-x-auto", children: /* @__PURE__ */ n("table", { className: "table table-zebra", children: [
    /* @__PURE__ */ t("thead", { children: /* @__PURE__ */ n("tr", { children: [
      /* @__PURE__ */ t("th", { children: "Stav" }),
      /* @__PURE__ */ t("th", { children: "Kategorie" }),
      /* @__PURE__ */ t("th", { children: "Priorita" }),
      /* @__PURE__ */ t("th", { children: "Název" }),
      /* @__PURE__ */ t("th", { children: "Vytvořil" }),
      /* @__PURE__ */ t("th", { children: "Vytvořeno" }),
      /* @__PURE__ */ t("th", { children: "Přílohy" })
    ] }) }),
    /* @__PURE__ */ t("tbody", { children: e.map((s) => /* @__PURE__ */ t(Ft, { ticket: s }, s.uuid)) })
  ] }) });
}
function fa({ tickets: e, filters: s, can: r }) {
  const [a, l] = f(s), [c, h] = f(!1), p = Y(null), x = ae((d) => {
    const o = new URLSearchParams();
    d.search && o.append("search", d.search), d.status && o.append("status", d.status), d.category && o.append("category", d.category), d.priority && o.append("priority", d.priority), d.all_orgs && o.append("all_orgs", "1"), $.get(`/tickets?${o.toString()}`, {}, {
      preserveState: !0,
      preserveScroll: !0,
      replace: !0
    });
  }, []);
  return G(() => (p.current && window.clearTimeout(p.current), p.current = window.setTimeout(() => {
    x(a);
  }, 300), () => {
    p.current && window.clearTimeout(p.current);
  }), [a]), /* @__PURE__ */ n(J, { children: [
    /* @__PURE__ */ t(Ce, { title: "Tickety" }),
    /* @__PURE__ */ n("div", { className: "space-y-4 p-4 md:p-6", children: [
      /* @__PURE__ */ n("div", { className: "flex flex-col md:flex-row md:items-center md:justify-between gap-3", children: [
        /* @__PURE__ */ n("div", { children: [
          /* @__PURE__ */ t("h1", { className: "text-2xl font-bold", children: "Tickety" }),
          /* @__PURE__ */ t("p", { className: "text-sm text-base-content/60", children: "Interní bug-tracker — hlášení chyb a požadavků na vylepšení." })
        ] }),
        /* @__PURE__ */ n(
          "button",
          {
            type: "button",
            className: "btn btn-primary",
            onClick: () => h(!0),
            children: [
              /* @__PURE__ */ t(it, { size: 18 }),
              "Nový ticket"
            ]
          }
        )
      ] }),
      /* @__PURE__ */ t(
        Pt,
        {
          filters: a,
          onChange: l,
          canViewAllOrgs: (r == null ? void 0 : r.viewAllOrgs) ?? !1
        }
      ),
      /* @__PURE__ */ t("div", { className: "card bg-base-100 shadow-sm", children: /* @__PURE__ */ t("div", { className: "card-body p-0", children: /* @__PURE__ */ t(Mt, { tickets: e.data }) }) }),
      e.last_page > 1 && /* @__PURE__ */ t("div", { className: "flex justify-center mt-4", children: /* @__PURE__ */ t("div", { className: "join", children: Array.from({ length: e.last_page }, (d, o) => o + 1).map((d) => {
        const o = new URLSearchParams();
        return a.search && o.append("search", a.search), a.status && o.append("status", a.status), a.category && o.append("category", a.category), a.priority && o.append("priority", a.priority), a.all_orgs && o.append("all_orgs", "1"), o.append("page", String(d)), /* @__PURE__ */ t(
          "button",
          {
            type: "button",
            className: `join-item btn btn-sm ${d === e.current_page ? "btn-active" : ""}`,
            onClick: () => $.get(`/tickets?${o.toString()}`, {}, {
              preserveState: !0,
              preserveScroll: !0
            }),
            children: d
          },
          d
        );
      }) }) })
    ] }),
    /* @__PURE__ */ t(
      xe,
      {
        open: c,
        onClose: () => h(!1),
        onCreated: () => $.reload({ only: ["tickets", "ticketsOpenCount"] })
      }
    )
  ] });
}
function Re(e) {
  return e.startsWith("image/") ? Ee : e === "application/pdf" ? de : e === "application/zip" || e === "application/x-zip-compressed" ? ot : e === "text/plain" || e === "text/csv" || e === "application/csv" ? de : e === "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" ? ct : e === "application/vnd.openxmlformats-officedocument.wordprocessingml.document" ? de : ge;
}
function Ue(e) {
  return e.startsWith("image/");
}
function Kt(e) {
  return e === "application/pdf";
}
const Bt = {
  title: "název",
  description: "popis",
  category: "kategorie",
  priority: "priorita",
  status: "stav",
  attachment_added: "přidána příloha",
  attachment_removed: "smazána příloha",
  comment_added: "přidal komentář",
  comment_deleted: "smazal komentář"
};
function Rt(e) {
  return e === "comment_added" || e === "comment_deleted" ? mt : e === "attachment_added" ? Ee : e === "attachment_removed" ? ve : de;
}
function oe(e) {
  return e === null ? "∅" : e.length > 50 ? e.substring(0, 50) + "…" : e;
}
function Ut(e) {
  const s = Bt[e.field];
  if (e.field === "comment_added" || e.field === "comment_deleted")
    return s;
  if (e.field === "attachment_added")
    return `přidal přílohu „${oe(e.new_value)}"`;
  if (e.field === "attachment_removed")
    return `smazal přílohu „${oe(e.old_value)}"`;
  const r = oe(e.old_value), a = oe(e.new_value);
  return `změnil ${s} z „${r}" na „${a}"`;
}
function Xt(e) {
  try {
    return Ae(new Date(e), { addSuffix: !0, locale: $e });
  } catch {
    return e;
  }
}
function Zt({ events: e }) {
  if (e.length === 0)
    return null;
  const s = [...e].reverse();
  return /* @__PURE__ */ n("details", { className: "collapse collapse-arrow bg-base-100 border border-base-300", children: [
    /* @__PURE__ */ t("summary", { className: "collapse-title font-medium text-sm", children: /* @__PURE__ */ n("span", { className: "flex items-center gap-2", children: [
      /* @__PURE__ */ t(dt, { size: 16 }),
      "Historie změn (",
      e.length,
      ")"
    ] }) }),
    /* @__PURE__ */ t("div", { className: "collapse-content", children: /* @__PURE__ */ t("ul", { className: "space-y-2 text-sm", children: s.map((r, a) => {
      var c;
      const l = Rt(r.field);
      return /* @__PURE__ */ n("li", { className: "flex items-start gap-2 text-base-content/70", children: [
        /* @__PURE__ */ t(l, { size: 14, className: "mt-0.5 flex-shrink-0" }),
        /* @__PURE__ */ n("div", { className: "flex-1", children: [
          /* @__PURE__ */ t("span", { className: "font-medium", children: ((c = r.user) == null ? void 0 : c.name) ?? "systém" }),
          " ",
          Ut(r),
          /* @__PURE__ */ t("span", { className: "text-xs text-base-content/40 ml-2", children: Xt(r.created_at) })
        ] })
      ] }, a);
    }) }) })
  ] });
}
const ce = 5e3;
function Ht({ ticketUuid: e }) {
  const [s, r] = f(""), [a, l] = f(!1), c = () => {
    const o = s.trim();
    !o || o.length > ce || a || (l(!0), $.post(
      `/tickets/${e}/comments`,
      { body: o },
      {
        preserveScroll: !0,
        onSuccess: () => {
          r(""), v.success("Komentář přidán");
        },
        onError: (N) => {
          const y = N.body ?? "Komentář se nepodařilo odeslat";
          v.error(typeof y == "string" ? y : "Chyba při odesílání");
        },
        onFinish: () => l(!1)
      }
    ));
  }, h = (o) => {
    o.preventDefault(), c();
  }, p = (o) => {
    (o.ctrlKey || o.metaKey) && o.key === "Enter" && (o.preventDefault(), c());
  }, x = ce - s.length, d = x < 0;
  return /* @__PURE__ */ n("form", { onSubmit: h, className: "flex flex-col gap-1", children: [
    /* @__PURE__ */ n("label", { className: "label py-1", children: [
      /* @__PURE__ */ t("span", { className: "text-sm font-medium", children: "Přidat komentář" }),
      /* @__PURE__ */ n("span", { className: "text-xs", children: [
        "Podporuje Markdown (",
        x,
        " / ",
        ce,
        " znaků)"
      ] })
    ] }),
    /* @__PURE__ */ t(
      "textarea",
      {
        className: `textarea textarea-bordered w-full min-h-24 ${d ? "textarea-error" : ""}`,
        placeholder: "Napište komentář… (Markdown: **tučně**, *kurzíva*, `kód`, - seznam)",
        value: s,
        onChange: (o) => r(o.target.value),
        onKeyDown: p,
        maxLength: ce + 100,
        disabled: a
      }
    ),
    /* @__PURE__ */ n("div", { className: "flex items-center justify-end gap-3 mt-2", children: [
      /* @__PURE__ */ t("span", { className: "text-xs text-base-content/50", children: "Ctrl+Enter pro odeslání" }),
      /* @__PURE__ */ n(
        "button",
        {
          type: "submit",
          className: "btn btn-primary btn-sm gap-1",
          disabled: !s.trim() || d || a,
          children: [
            a && /* @__PURE__ */ t("span", { className: "loading loading-spinner loading-xs" }),
            /* @__PURE__ */ t(ut, { size: 14 }),
            "Odeslat"
          ]
        }
      )
    ] })
  ] });
}
function Vt(e) {
  return e ? e.split(" ").map((s) => s[0]).slice(0, 2).join("").toUpperCase() : "?";
}
function Yt(e) {
  try {
    return Ae(new Date(e), { addSuffix: !0, locale: $e });
  } catch {
    return e;
  }
}
function Gt({ comment: e, isOwn: s }) {
  var o, N;
  const [r, a] = f(!1), [l, c] = f(e.body), [h, p] = f(!1), x = () => {
    !l.trim() || h || (p(!0), $.patch(
      `/comments/${e.uuid}`,
      { body: l.trim() },
      {
        preserveScroll: !0,
        onSuccess: () => {
          a(!1), v.success("Komentář upraven");
        },
        onError: (y) => {
          const T = y.body ?? "Úpravu se nepodařilo uložit (možná vypršelo 5 min okno)";
          v.error(typeof T == "string" ? T : "Chyba");
        },
        onFinish: () => p(!1)
      }
    ));
  }, d = () => {
    confirm("Opravdu smazat komentář? Akce je nevratná.") && (p(!0), $.delete(`/comments/${e.uuid}`, {
      preserveScroll: !0,
      onSuccess: () => v.success("Komentář smazán"),
      onError: () => v.error("Smazání selhalo"),
      onFinish: () => p(!1)
    }));
  };
  return (
    // Anchor pro notifikace cílící na /tickets/{uuid}#comment-{uuid}
    /* @__PURE__ */ n("div", { id: `comment-${e.uuid}`, className: `chat ${s ? "chat-end" : "chat-start"}`, children: [
      /* @__PURE__ */ t("div", { className: "chat-image avatar avatar-placeholder", children: /* @__PURE__ */ t("div", { className: "bg-neutral text-neutral-content rounded-full w-8 h-8", children: /* @__PURE__ */ t("span", { className: "text-xs", children: Vt((o = e.author) == null ? void 0 : o.name) }) }) }),
      /* @__PURE__ */ n("div", { className: "chat-header", children: [
        /* @__PURE__ */ t("span", { className: "font-medium", children: ((N = e.author) == null ? void 0 : N.name) ?? "—" }),
        /* @__PURE__ */ t("time", { className: "text-xs opacity-60 ml-2", children: Yt(e.created_at) }),
        e.updated_at !== e.created_at && /* @__PURE__ */ t("span", { className: "text-xs opacity-40 ml-1 italic", children: "(upraveno)" })
      ] }),
      r ? (
        // Editační režim — textarea + akce mimo bublinu kvůli kontrastu
        /* @__PURE__ */ n("div", { className: "chat-bubble bg-base-200 text-base-content", children: [
          /* @__PURE__ */ t(
            "textarea",
            {
              className: "textarea textarea-bordered w-full min-h-20 text-sm",
              value: l,
              onChange: (y) => c(y.target.value),
              maxLength: 5e3,
              disabled: h
            }
          ),
          /* @__PURE__ */ n("div", { className: "flex justify-end gap-1 mt-2", children: [
            /* @__PURE__ */ n(
              "button",
              {
                type: "button",
                className: "btn btn-ghost btn-xs",
                onClick: () => {
                  a(!1), c(e.body);
                },
                disabled: h,
                children: [
                  /* @__PURE__ */ t(W, { size: 12 }),
                  " Zrušit"
                ]
              }
            ),
            /* @__PURE__ */ n(
              "button",
              {
                type: "button",
                className: "btn btn-primary btn-xs",
                onClick: x,
                disabled: !l.trim() || h,
                children: [
                  /* @__PURE__ */ t(Ne, { size: 12 }),
                  " Uložit"
                ]
              }
            )
          ] })
        ] })
      ) : /* @__PURE__ */ t("div", { className: `chat-bubble ${s ? "chat-bubble-primary" : ""}`, children: /* @__PURE__ */ t(
        "div",
        {
          className: "text-sm break-words [&_a]:underline [&_p]:my-1 [&_ul]:list-disc [&_ul]:pl-5 [&_ol]:list-decimal [&_ol]:pl-5 [&_code]:px-1 [&_code]:rounded [&_code]:bg-base-content/10",
          dangerouslySetInnerHTML: { __html: e.body_html }
        }
      ) }),
      !r && (e.can_edit || e.can_delete) && /* @__PURE__ */ n("div", { className: "chat-footer flex gap-1 mt-1", children: [
        e.can_edit && /* @__PURE__ */ n(
          "button",
          {
            type: "button",
            className: "btn btn-ghost btn-xs",
            onClick: () => a(!0),
            title: "Upravit (do 5 min od vytvoření)",
            disabled: h,
            children: [
              /* @__PURE__ */ t(De, { size: 12 }),
              " Upravit"
            ]
          }
        ),
        e.can_delete && /* @__PURE__ */ n(
          "button",
          {
            type: "button",
            className: "btn btn-ghost btn-xs text-error",
            onClick: d,
            title: "Smazat",
            disabled: h,
            children: [
              /* @__PURE__ */ t(ve, { size: 12 }),
              " Smazat"
            ]
          }
        )
      ] })
    ] })
  );
}
function Wt({ comments: e }) {
  var a;
  const s = nt().props.auth, r = (a = s == null ? void 0 : s.user) == null ? void 0 : a.id;
  return e.length === 0 ? /* @__PURE__ */ t("p", { className: "text-sm text-base-content/60 italic", children: "Zatím žádné komentáře. Buďte první." }) : /* @__PURE__ */ t("div", { className: "space-y-1", children: e.map((l) => {
    var c;
    return /* @__PURE__ */ t(
      Gt,
      {
        comment: l,
        isOwn: ((c = l.author) == null ? void 0 : c.id) != null && l.author.id === r
      },
      l.uuid
    );
  }) });
}
function qt(e) {
  return e < 1024 ? `${e} B` : e < 1024 * 1024 ? `${(e / 1024).toFixed(1)} KB` : `${(e / 1024 / 1024).toFixed(2)} MB`;
}
function Jt({ attachment: e, onClose: s }) {
  if (!e) return null;
  const r = Re(e.mime_type);
  return /* @__PURE__ */ t("div", { className: "modal modal-open", onClick: s, children: /* @__PURE__ */ n(
    "div",
    {
      className: "modal-box max-w-5xl w-full p-2 bg-base-100",
      onClick: (a) => a.stopPropagation(),
      children: [
        /* @__PURE__ */ n("div", { className: "flex justify-between items-center mb-2 px-2", children: [
          /* @__PURE__ */ t("h3", { className: "font-medium truncate", children: e.filename }),
          /* @__PURE__ */ t(
            "button",
            {
              type: "button",
              className: "btn btn-ghost btn-sm btn-circle",
              onClick: s,
              "aria-label": "Zavřít",
              children: /* @__PURE__ */ t(W, { size: 18 })
            }
          )
        ] }),
        Ue(e.mime_type) ? /* @__PURE__ */ t(
          "img",
          {
            src: e.signed_url,
            alt: e.filename,
            className: "w-full h-auto max-h-[80vh] object-contain rounded"
          }
        ) : Kt(e.mime_type) ? /* @__PURE__ */ t(
          "iframe",
          {
            src: e.signed_url,
            className: "w-full h-[80vh] rounded",
            title: e.filename
          }
        ) : (
          // Ostatní typy — ikona + filename + download button
          /* @__PURE__ */ n("div", { className: "flex flex-col items-center justify-center gap-3 py-12", children: [
            /* @__PURE__ */ t(r, { size: 64, className: "text-base-content/60" }),
            /* @__PURE__ */ t("div", { className: "font-medium text-center break-all px-4", children: e.filename }),
            /* @__PURE__ */ t("div", { className: "text-sm text-base-content/60", children: qt(e.size_bytes) }),
            /* @__PURE__ */ n(
              "a",
              {
                href: e.signed_url,
                download: !0,
                className: "btn btn-primary mt-4 gap-2",
                children: [
                  /* @__PURE__ */ t(pt, { size: 16 }),
                  "Stáhnout"
                ]
              }
            )
          ] })
        )
      ]
    }
  ) });
}
const Qt = 12e3, _e = (e) => Fe(e);
function ea(e) {
  return e < 1024 ? `${e} B` : e < 1024 * 1024 ? `${(e / 1024).toFixed(1)} KB` : `${(e / 1024 / 1024).toFixed(2)} MB`;
}
function ta({ ticket: e }) {
  var D, X, F, R, Z, ie;
  const [s, r] = f(null), [a, l] = f(!1), c = ((D = e.can) == null ? void 0 : D.update) ?? !1, [h, p] = f(!1), [x, d] = f(e.title), [o, N] = f(e.description), [y, T] = f(e.category), [L, P] = f(e.priority), S = Y(null);
  G(() => {
    let m;
    const A = () => {
      $.reload({
        only: ["ticket"]
      });
    }, O = () => {
      m === void 0 && (m = window.setInterval(A, Qt));
    }, H = () => {
      m !== void 0 && (window.clearInterval(m), m = void 0);
    }, M = () => {
      document.hidden ? H() : (A(), O());
    };
    return document.hidden || O(), document.addEventListener("visibilitychange", M), () => {
      H(), document.removeEventListener("visibilitychange", M);
    };
  }, []);
  const u = () => {
    l(!0), $.patch(
      `/tickets/${e.uuid}`,
      {
        title: x,
        description: o,
        category: y,
        priority: L
      },
      {
        preserveScroll: !0,
        onSuccess: () => {
          p(!1), v.success("Ticket upraven");
        },
        onError: () => v.error("Úprava selhala"),
        onFinish: () => l(!1)
      }
    );
  }, b = () => {
    d(e.title), N(e.description), T(e.category), P(e.priority), p(!1);
  }, z = (m) => {
    var O;
    const A = (O = m.target.files) == null ? void 0 : O[0];
    A && (l(!0), $.post(
      `/tickets/${e.uuid}/attachments`,
      { file: A },
      {
        forceFormData: !0,
        preserveScroll: !0,
        onSuccess: () => v.success("Příloha přidána"),
        onError: (H) => {
          const M = H.file ?? "Nahrání selhalo";
          v.error(typeof M == "string" ? M : "Chyba");
        },
        onFinish: () => {
          l(!1), S.current && (S.current.value = "");
        }
      }
    ));
  }, I = (m) => {
    confirm(`Smazat přílohu „${m.filename}"?`) && (l(!0), $.delete(`/tickets/${e.uuid}/attachments/${m.uuid}`, {
      preserveScroll: !0,
      onSuccess: () => v.success("Příloha smazána"),
      onError: () => v.error("Smazání selhalo"),
      onFinish: () => l(!1)
    }));
  }, C = () => {
    const m = e.status === "open" ? "close" : "reopen";
    l(!0), $.post(
      `/tickets/${e.uuid}/${m}`,
      {},
      {
        preserveScroll: !0,
        onFinish: () => l(!1),
        onSuccess: () => {
          v.success(m === "close" ? "Ticket byl zavřen" : "Ticket byl znovu otevřen");
        },
        onError: () => v.error("Akce selhala")
      }
    );
  }, Q = async () => {
    try {
      const m = await fetch(`/api/tickets/${e.uuid}/export.md`, {
        method: "GET",
        credentials: "include",
        headers: {
          Accept: "text/markdown",
          ...Le()
        }
      });
      if (!m.ok)
        throw new Error(`HTTP ${m.status}`);
      const O = `/validate

${await m.text()}

Zvaliduj tento bug — analyzuj příčinu a připrav podklad pro opravu.`;
      await navigator.clipboard.writeText(O), v.success("Zkopírováno do schránky");
    } catch (m) {
      const A = m instanceof Error ? m.message : "Neznámá chyba";
      v.error(`Nepodařilo se zkopírovat: ${A}`);
    }
  }, U = () => {
    window.open(`/api/tickets/${e.uuid}/export.md`, "_blank");
  };
  return /* @__PURE__ */ n("div", { className: "space-y-4", children: [
    /* @__PURE__ */ n("div", { className: "flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4", children: [
      /* @__PURE__ */ n("div", { className: "flex-1", children: [
        /* @__PURE__ */ n("div", { className: "flex flex-wrap items-center gap-2 mb-2", children: [
          /* @__PURE__ */ t("span", { className: `badge ${Me[e.status]}`, children: ue[e.status] }),
          /* @__PURE__ */ t("span", { className: `badge ${Be[e.category]}`, children: K[e.category] }),
          /* @__PURE__ */ t("span", { className: `badge ${Ke[e.priority]}`, children: B[e.priority] })
        ] }),
        /* @__PURE__ */ t("h1", { className: "text-2xl font-bold", children: e.title })
      ] }),
      /* @__PURE__ */ n("div", { className: "flex flex-wrap gap-2", children: [
        c && !h && /* @__PURE__ */ n(
          "button",
          {
            type: "button",
            className: "btn btn-sm btn-outline btn-primary",
            onClick: () => p(!0),
            children: [
              /* @__PURE__ */ t(De, { size: 16 }),
              "Upravit"
            ]
          }
        ),
        /* @__PURE__ */ n(
          "button",
          {
            type: "button",
            className: "btn btn-sm btn-ghost",
            onClick: Q,
            children: [
              /* @__PURE__ */ t(ht, { size: 16 }),
              "Kopírovat jako Claude prompt"
            ]
          }
        ),
        /* @__PURE__ */ n(
          "button",
          {
            type: "button",
            className: "btn btn-sm btn-ghost",
            onClick: U,
            children: [
              /* @__PURE__ */ t(bt, { size: 16 }),
              "Otevřít markdown"
            ]
          }
        ),
        /* @__PURE__ */ n(
          "button",
          {
            type: "button",
            className: `btn btn-sm ${e.status === "open" ? "btn-error" : "btn-success"}`,
            onClick: C,
            disabled: a,
            children: [
              e.status === "open" ? /* @__PURE__ */ t(ft, { size: 16 }) : /* @__PURE__ */ t(gt, { size: 16 }),
              e.status === "open" ? "Zavřít" : "Znovu otevřít"
            ]
          }
        )
      ] })
    ] }),
    h && /* @__PURE__ */ t("div", { className: "card bg-base-100 border-2 border-warning shadow-sm", children: /* @__PURE__ */ n("div", { className: "card-body p-4", children: [
      /* @__PURE__ */ t("h2", { className: "card-title text-lg", children: "Úprava ticketu" }),
      /* @__PURE__ */ n("div", { className: "flex flex-col gap-1", children: [
        /* @__PURE__ */ t("label", { className: "label py-1", children: /* @__PURE__ */ t("span", { className: "text-sm font-medium", children: "Název" }) }),
        /* @__PURE__ */ t(
          "input",
          {
            type: "text",
            className: "input input-bordered w-full",
            value: x,
            onChange: (m) => d(m.target.value),
            maxLength: 255,
            disabled: a
          }
        )
      ] }),
      /* @__PURE__ */ n("div", { className: "grid grid-cols-1 md:grid-cols-2 gap-3 mt-2", children: [
        /* @__PURE__ */ n("div", { className: "flex flex-col gap-1", children: [
          /* @__PURE__ */ t("label", { className: "label py-1", children: /* @__PURE__ */ t("span", { className: "text-sm font-medium", children: "Kategorie" }) }),
          /* @__PURE__ */ t(
            "select",
            {
              className: "select select-bordered w-full",
              value: y,
              onChange: (m) => T(m.target.value),
              disabled: a,
              children: Object.keys(K).map((m) => /* @__PURE__ */ t("option", { value: m, children: K[m] }, m))
            }
          )
        ] }),
        /* @__PURE__ */ n("div", { className: "flex flex-col gap-1", children: [
          /* @__PURE__ */ t("label", { className: "label py-1", children: /* @__PURE__ */ t("span", { className: "text-sm font-medium", children: "Priorita" }) }),
          /* @__PURE__ */ t(
            "select",
            {
              className: "select select-bordered w-full",
              value: L,
              onChange: (m) => P(m.target.value),
              disabled: a,
              children: Object.keys(B).map((m) => /* @__PURE__ */ t("option", { value: m, children: B[m] }, m))
            }
          )
        ] })
      ] }),
      /* @__PURE__ */ n("div", { className: "flex flex-col gap-1 mt-2", children: [
        /* @__PURE__ */ t("label", { className: "label py-1", children: /* @__PURE__ */ t("span", { className: "text-sm font-medium", children: "Popis" }) }),
        /* @__PURE__ */ t(
          "textarea",
          {
            className: "textarea textarea-bordered w-full min-h-32",
            value: o,
            onChange: (m) => N(m.target.value),
            maxLength: 1e4,
            disabled: a
          }
        )
      ] }),
      /* @__PURE__ */ n("div", { className: "flex justify-end gap-2 mt-2", children: [
        /* @__PURE__ */ n(
          "button",
          {
            type: "button",
            className: "btn btn-ghost btn-sm",
            onClick: b,
            disabled: a,
            children: [
              /* @__PURE__ */ t(W, { size: 14 }),
              " Zrušit"
            ]
          }
        ),
        /* @__PURE__ */ n(
          "button",
          {
            type: "button",
            className: "btn btn-primary btn-sm",
            onClick: u,
            disabled: a || !x.trim() || !o.trim(),
            children: [
              a && /* @__PURE__ */ t("span", { className: "loading loading-spinner loading-xs" }),
              /* @__PURE__ */ t(Ne, { size: 14 }),
              " Uložit změny"
            ]
          }
        )
      ] })
    ] }) }),
    /* @__PURE__ */ t("div", { className: "card bg-base-100 shadow-sm", children: /* @__PURE__ */ t("div", { className: "card-body p-4", children: /* @__PURE__ */ n("div", { className: "grid grid-cols-1 md:grid-cols-2 gap-3 text-sm", children: [
      /* @__PURE__ */ n("div", { children: [
        /* @__PURE__ */ t("span", { className: "text-base-content/60", children: "Vytvořil:" }),
        " ",
        /* @__PURE__ */ t("span", { className: "font-medium", children: ((X = e.creator) == null ? void 0 : X.name) ?? "—" }),
        ((F = e.creator) == null ? void 0 : F.email) && /* @__PURE__ */ n("span", { className: "text-base-content/60", children: [
          " (",
          e.creator.email,
          ")"
        ] })
      ] }),
      /* @__PURE__ */ n("div", { children: [
        /* @__PURE__ */ t("span", { className: "text-base-content/60", children: "Organizace:" }),
        " ",
        /* @__PURE__ */ t("span", { className: "font-medium", children: ((R = e.organization) == null ? void 0 : R.name) ?? "—" })
      ] }),
      /* @__PURE__ */ n("div", { children: [
        /* @__PURE__ */ t("span", { className: "text-base-content/60", children: "Vytvořeno:" }),
        " ",
        /* @__PURE__ */ t("span", { className: "font-medium", children: _e(e.created_at) })
      ] }),
      e.closed_at && /* @__PURE__ */ n("div", { children: [
        /* @__PURE__ */ t("span", { className: "text-base-content/60", children: "Zavřeno:" }),
        " ",
        /* @__PURE__ */ t("span", { className: "font-medium", children: _e(e.closed_at) })
      ] }),
      e.page_url && /* @__PURE__ */ n("div", { className: "md:col-span-2", children: [
        /* @__PURE__ */ t("span", { className: "text-base-content/60", children: "URL stránky:" }),
        " ",
        /* @__PURE__ */ t("code", { className: "text-xs bg-base-200 px-1.5 py-0.5 rounded", children: e.page_url })
      ] }),
      e.viewport && /* @__PURE__ */ n("div", { children: [
        /* @__PURE__ */ t("span", { className: "text-base-content/60", children: "Viewport:" }),
        " ",
        /* @__PURE__ */ t("span", { className: "font-mono text-xs", children: e.viewport })
      ] }),
      e.user_agent && /* @__PURE__ */ n("div", { className: "md:col-span-2", children: [
        /* @__PURE__ */ t("span", { className: "text-base-content/60", children: "User-Agent:" }),
        " ",
        /* @__PURE__ */ t("span", { className: "text-xs text-base-content/70 break-all", children: e.user_agent })
      ] })
    ] }) }) }),
    /* @__PURE__ */ t("div", { className: "card bg-base-100 shadow-sm", children: /* @__PURE__ */ n("div", { className: "card-body p-4", children: [
      /* @__PURE__ */ t("h2", { className: "card-title text-lg", children: "Popis" }),
      /* @__PURE__ */ t("p", { className: "whitespace-pre-wrap text-sm text-base-content/80", children: e.description })
    ] }) }),
    /* @__PURE__ */ t("div", { className: "card bg-base-100 shadow-sm", children: /* @__PURE__ */ n("div", { className: "card-body p-4", children: [
      /* @__PURE__ */ n("div", { className: "flex items-center justify-between gap-2", children: [
        /* @__PURE__ */ n("h2", { className: "card-title text-lg", children: [
          "Přílohy (",
          ((Z = e.attachments) == null ? void 0 : Z.length) ?? 0,
          ")"
        ] }),
        c && /* @__PURE__ */ n(J, { children: [
          /* @__PURE__ */ t(
            "input",
            {
              ref: S,
              type: "file",
              className: "hidden",
              onChange: z,
              disabled: a
            }
          ),
          /* @__PURE__ */ n(
            "button",
            {
              type: "button",
              className: "btn btn-sm btn-ghost gap-1",
              onClick: () => {
                var m;
                return (m = S.current) == null ? void 0 : m.click();
              },
              disabled: a,
              children: [
                /* @__PURE__ */ t(ze, { size: 14 }),
                " Přidat"
              ]
            }
          )
        ] })
      ] }),
      e.attachments && e.attachments.length > 0 ? /* @__PURE__ */ t("div", { className: "grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 mt-2", children: e.attachments.map((m) => {
        const A = Re(m.mime_type);
        return /* @__PURE__ */ n(
          "div",
          {
            className: "relative overflow-hidden rounded-lg border border-base-300 hover:border-primary transition-colors group",
            children: [
              /* @__PURE__ */ n(
                "button",
                {
                  type: "button",
                  onClick: () => r(m),
                  className: "block w-full text-left",
                  title: m.filename,
                  children: [
                    Ue(m.mime_type) ? /* @__PURE__ */ t(
                      "img",
                      {
                        src: m.signed_url,
                        alt: m.filename,
                        className: "w-full h-32 object-cover",
                        loading: "lazy"
                      }
                    ) : /* @__PURE__ */ t("div", { className: "w-full h-32 flex items-center justify-center bg-base-200", children: /* @__PURE__ */ t(A, { size: 48, className: "text-base-content/60" }) }),
                    /* @__PURE__ */ n("div", { className: "px-2 py-1.5 bg-base-100 border-t border-base-300", children: [
                      /* @__PURE__ */ t("div", { className: "text-xs font-medium truncate", children: m.filename }),
                      /* @__PURE__ */ t("div", { className: "text-xs text-base-content/60", children: ea(m.size_bytes) })
                    ] })
                  ]
                }
              ),
              c && /* @__PURE__ */ t(
                "button",
                {
                  type: "button",
                  onClick: () => I(m),
                  className: "absolute top-1 right-1 btn btn-xs btn-circle btn-error opacity-0 group-hover:opacity-100 transition-opacity",
                  title: "Smazat přílohu",
                  "aria-label": "Smazat přílohu",
                  disabled: a,
                  children: /* @__PURE__ */ t(ve, { size: 12 })
                }
              )
            ]
          },
          m.uuid
        );
      }) }) : /* @__PURE__ */ t("p", { className: "text-sm text-base-content/60 italic mt-2", children: "Žádné přílohy." })
    ] }) }),
    /* @__PURE__ */ t("div", { className: "card bg-base-100 shadow-sm", children: /* @__PURE__ */ n("div", { className: "card-body p-4", children: [
      /* @__PURE__ */ n("h2", { className: "card-title text-lg", children: [
        "Komentáře (",
        ((ie = e.comments) == null ? void 0 : ie.length) ?? 0,
        ")"
      ] }),
      /* @__PURE__ */ t(Wt, { comments: e.comments ?? [] }),
      /* @__PURE__ */ t("div", { className: "divider my-2" }),
      /* @__PURE__ */ t(Ht, { ticketUuid: e.uuid })
    ] }) }),
    e.audit_log && e.audit_log.length > 0 && /* @__PURE__ */ t(Zt, { events: e.audit_log }),
    /* @__PURE__ */ t(Jt, { attachment: s, onClose: () => r(null) })
  ] });
}
function ga({ ticket: e }) {
  return /* @__PURE__ */ n(J, { children: [
    /* @__PURE__ */ t(Ce, { title: `Ticket: ${e.title}` }),
    /* @__PURE__ */ n("div", { className: "space-y-4 p-4 md:p-6", children: [
      /* @__PURE__ */ n(st, { href: "/tickets", className: "btn btn-ghost btn-sm", children: [
        /* @__PURE__ */ t(xt, { size: 16 }),
        "Zpět na seznam"
      ] }),
      /* @__PURE__ */ t(ta, { ticket: e })
    ] })
  ] });
}
const fe = "ticketsFabHidden";
function xa() {
  const [e, s] = f(() => typeof window > "u" ? !1 : sessionStorage.getItem(fe) === "true"), [r, a] = f(!1);
  return G(() => {
    typeof window < "u" && localStorage.removeItem(fe);
  }, []), G(() => {
    sessionStorage.setItem(fe, String(e));
  }, [e]), e ? r ? /* @__PURE__ */ t(xe, { open: r, onClose: () => a(!1) }) : null : /* @__PURE__ */ n(J, { children: [
    /* @__PURE__ */ t("div", { className: "fixed bottom-4 right-4 z-50", children: /* @__PURE__ */ t("div", { className: "tooltip tooltip-left", "data-tip": "Nahlásit problém", children: /* @__PURE__ */ n(
      "button",
      {
        type: "button",
        onClick: () => a(!0),
        className: "btn btn-circle btn-error shadow-lg transition-transform hover:scale-105 relative",
        "aria-label": "Nahlásit problém",
        children: [
          /* @__PURE__ */ t(Nt, { size: 20 }),
          /* @__PURE__ */ t(
            "span",
            {
              role: "button",
              tabIndex: 0,
              "aria-label": "Schovat",
              title: "Schovat",
              onClick: (l) => {
                l.stopPropagation(), s(!0);
              },
              onKeyDown: (l) => {
                (l.key === "Enter" || l.key === " ") && (l.stopPropagation(), l.preventDefault(), s(!0));
              },
              className: "absolute -top-1 -right-1 h-4 w-4 rounded-full cursor-pointer bg-base-100 border border-base-300 text-base-content hover:bg-base-200 flex items-center justify-center",
              children: /* @__PURE__ */ t(W, { size: 10, strokeWidth: 2.5 })
            }
          )
        ]
      }
    ) }) }),
    /* @__PURE__ */ t(xe, { open: r, onClose: () => a(!1) })
  ] });
}
export {
  je as ApiError,
  wt as Button,
  kt as Checkbox,
  It as DocumentDropZone,
  Ie as Input,
  jt as ScreenshotPicker,
  Tt as Select,
  Be as TICKET_CATEGORY_BADGE_CLASS,
  K as TICKET_CATEGORY_LABELS,
  Ke as TICKET_PRIORITY_BADGE_CLASS,
  B as TICKET_PRIORITY_LABELS,
  Me as TICKET_STATUS_BADGE_CLASS,
  ue as TICKET_STATUS_LABELS,
  da as TextInput,
  St as Textarea,
  Zt as TicketAuditTimeline,
  Ht as TicketCommentComposer,
  Wt as TicketComments,
  xe as TicketCreateModal,
  ta as TicketDetail,
  ga as TicketDetailPage,
  Ft as TicketRow,
  Jt as TicketScreenshotLightbox,
  xa as TicketsFab,
  Pt as TicketsFilters,
  fa as TicketsIndexPage,
  Mt as TicketsList,
  Dt as api,
  te as apiFetch,
  Et as apiUpload,
  se as cn,
  Le as csrfHeaders,
  Oe as extractErrorMessage,
  ua as formatDate,
  pa as formatDateLong,
  Fe as formatDateTime,
  ba as formatRelative,
  ha as formatTime,
  _t as getXsrfToken,
  ma as isAbortError,
  Pe as refreshCsrfCookie
};
//# sourceMappingURL=index.js.map
