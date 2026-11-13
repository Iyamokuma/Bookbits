import { useCallback, useEffect, useRef, useState } from 'react';
import { api } from '../lib/api';

/**
 * A small WYSIWYG editor for admin page content, built on contentEditable so
 * it adds nothing to the storefront bundle. The server sanitises whatever this
 * produces before storing it (server/lib/sanitize.js); the paste cleaner here
 * just keeps the editor looking like the published page in the meantime.
 */

const KEEP_TAGS = new Set([
  'P', 'BR', 'DIV', 'H2', 'H3', 'H4', 'STRONG', 'B', 'EM', 'I', 'U', 'S',
  'BLOCKQUOTE', 'UL', 'OL', 'LI', 'HR', 'A', 'IMG', 'FIGURE', 'FIGCAPTION',
]);
const DROP_TAGS = new Set(['SCRIPT', 'STYLE', 'META', 'LINK', 'TITLE', 'IFRAME', 'OBJECT', 'SVG']);

const wrap = (tag, nodes) => {
  const el = document.createElement(tag);
  nodes.forEach((n) => el.appendChild(n));
  return el;
};

/** Reduce pasted HTML (Word, Google Docs, web pages) to plain formatting. */
function cleanPastedHtml(html) {
  const doc = new DOMParser().parseFromString(html, 'text/html');

  const walk = (node) => {
    for (const child of [...node.childNodes]) {
      if (child.nodeType === Node.COMMENT_NODE) {
        child.remove();
        continue;
      }
      if (child.nodeType !== Node.ELEMENT_NODE) continue;

      const tag = child.tagName.toUpperCase();
      if (DROP_TAGS.has(tag) || tag.includes(':')) {
        child.remove();
        continue;
      }
      walk(child);

      const style = child.getAttribute('style') || '';
      // Google Docs wraps the whole clipboard in <b style="font-weight:normal">.
      if (tag === 'B' && /font-weight:\s*(normal|400)/.test(style)) {
        child.replaceWith(...child.childNodes);
        continue;
      }
      if (tag === 'H1') {
        child.replaceWith(wrap('h2', [...child.childNodes]));
        continue;
      }
      if (!KEEP_TAGS.has(tag)) {
        // Bold/italic/underline that only exists as inline style (Google Docs).
        let nodes = [...child.childNodes];
        if (/font-weight:\s*(bold|[6-9]00)/.test(style)) nodes = [wrap('strong', nodes)];
        if (/font-style:\s*italic/.test(style)) nodes = [wrap('em', nodes)];
        if (/text-decoration[^;]*underline/.test(style)) nodes = [wrap('u', nodes)];
        child.replaceWith(...nodes);
        continue;
      }
      for (const attr of [...child.attributes]) {
        const keep =
          (tag === 'A' && attr.name === 'href') ||
          (tag === 'IMG' && (attr.name === 'src' || attr.name === 'alt'));
        if (!keep) child.removeAttribute(attr.name);
      }
    }
  };

  walk(doc.body);
  return doc.body.innerHTML;
}

/** The editor's HTML, or '' when it holds nothing but an empty paragraph. */
const contentOf = (el) =>
  !el.textContent.trim() && !el.querySelector('img, hr') ? '' : el.innerHTML;

const escapeHtml = (s) =>
  String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

const BLOCKS = [
  { value: 'p', label: 'Paragraph' },
  { value: 'h2', label: 'Heading' },
  { value: 'h3', label: 'Subheading' },
  { value: 'blockquote', label: 'Quote' },
];

const Icon = ({ d, className = 'h-4 w-4' }) => (
  <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"
       strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
    <path d={d} />
  </svg>
);

const ICONS = {
  ul: 'M9 6h11M9 12h11M9 18h11M4.5 6h.01M4.5 12h.01M4.5 18h.01',
  ol: 'M10 6h10M10 12h10M10 18h10M4 5h1v4M4 9h2M4 14.5a1 1 0 0 1 2 .5c0 .8-2 1.5-2 3h2',
  link: 'M10 14a4 4 0 0 0 5.66 0l3-3a4 4 0 0 0-5.66-5.66l-1 1M14 10a4 4 0 0 0-5.66 0l-3 3a4 4 0 0 0 5.66 5.66l1-1',
  unlink: 'M15 9l-6 6M10 6.5l1-1a4 4 0 0 1 5.66 5.66l-1 1M14 17.5l-1 1a4 4 0 0 1-5.66-5.66l1-1',
  image: 'M4 5h16v14H4zM4 15l4-4 4 4 3-3 5 5M15 9h.01',
  hr: 'M4 12h16',
  clear: 'M6 6h12M12 6l-3 12M5 19l14-14',
  undo: 'M9 14L4 9l5-5M4 9h11a5 5 0 0 1 0 10h-3',
  redo: 'M15 14l5-5-5-5M20 9H9a5 5 0 0 0 0 10h3',
};

/** mousedown would otherwise move focus out of the editor and lose the selection. */
const keepFocus = (e) => e.preventDefault();

function ToolbarButton({ label, isActive, onClick, disabled, locked, children }) {
  return (
    <button
      type="button"
      title={label}
      aria-label={label}
      aria-pressed={isActive || undefined}
      onMouseDown={keepFocus}
      onClick={onClick}
      disabled={disabled || locked}
      className={`inline-flex h-8 min-w-8 items-center justify-center rounded-md px-2 text-sm transition disabled:opacity-40 ${
        isActive ? 'bg-brand-100 text-brand-800' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
      }`}
    >
      {children}
    </button>
  );
}

export default function RichTextEditor({ value, onChange, placeholder = 'Start writing…' }) {
  const editorRef = useRef(null);
  const fileRef = useRef(null);
  const savedRange = useRef(null);
  const [mode, setMode] = useState('visual');
  const [active, setActive] = useState({});
  const [uploading, setUploading] = useState(false);
  const [uploadError, setUploadError] = useState(null);

  // Load outside changes (initial data, HTML mode edits) without clobbering the
  // caret while typing: typed changes already match the DOM, so they're skipped.
  useEffect(() => {
    const el = editorRef.current;
    if (el && mode === 'visual' && contentOf(el) !== (value || '')) el.innerHTML = value || '';
  }, [value, mode]);

  const emit = useCallback(() => {
    if (editorRef.current) onChange(contentOf(editorRef.current));
  }, [onChange]);

  const refreshActive = useCallback(() => {
    const el = editorRef.current;
    const sel = window.getSelection();
    if (!el || !sel?.anchorNode || !el.contains(sel.anchorNode)) return;
    setActive({
      bold: document.queryCommandState('bold'),
      italic: document.queryCommandState('italic'),
      underline: document.queryCommandState('underline'),
      ul: document.queryCommandState('insertUnorderedList'),
      ol: document.queryCommandState('insertOrderedList'),
      block: String(document.queryCommandValue('formatBlock') || 'p').toLowerCase(),
    });
  }, []);

  useEffect(() => {
    document.addEventListener('selectionchange', refreshActive);
    return () => document.removeEventListener('selectionchange', refreshActive);
  }, [refreshActive]);

  const saveSelection = () => {
    const sel = window.getSelection();
    if (sel?.rangeCount && editorRef.current?.contains(sel.anchorNode)) {
      savedRange.current = sel.getRangeAt(0).cloneRange();
    }
  };

  const restoreSelection = () => {
    const el = editorRef.current;
    el?.focus();
    if (savedRange.current) {
      const sel = window.getSelection();
      sel.removeAllRanges();
      sel.addRange(savedRange.current);
    }
  };

  const exec = (command, arg = null) => {
    editorRef.current?.focus();
    document.execCommand(command, false, arg);
    emit();
    refreshActive();
  };

  // Chrome turns a <p> into a list by nesting <ul> inside it, which reloads as
  // empty paragraphs around the list. A <div> line is converted cleanly.
  const toggleList = (command) => {
    editorRef.current?.focus();
    const inList =
      document.queryCommandState('insertUnorderedList') || document.queryCommandState('insertOrderedList');
    const block = String(document.queryCommandValue('formatBlock') || '').toLowerCase();
    if (!inList && block === 'p') document.execCommand('formatBlock', false, '<div>');
    exec(command);
  };

  const onFocus = () => {
    // Enter makes <p> rather than Chrome's default <div>.
    document.execCommand('defaultParagraphSeparator', false, 'p');

    // Text typed into a bare contentEditable lands outside any block, and the
    // first list then nests inside a <p>. Start empty editors in a paragraph.
    const el = editorRef.current;
    if (el && !el.innerHTML.trim()) {
      el.innerHTML = '<p><br></p>';
      const range = document.createRange();
      range.setStart(el.firstChild, 0);
      range.collapse(true);
      const sel = window.getSelection();
      sel.removeAllRanges();
      sel.addRange(range);
    }
  };

  const onPaste = (e) => {
    const html = e.clipboardData?.getData('text/html');
    const text = e.clipboardData?.getData('text/plain');
    if (!html && !text) return;
    e.preventDefault();
    if (html) {
      document.execCommand('insertHTML', false, cleanPastedHtml(html));
    } else {
      const paragraphs = text.split(/\r?\n\s*\r?\n/).map((p) => escapeHtml(p).replace(/\r?\n/g, '<br>'));
      document.execCommand('insertHTML', false, paragraphs.map((p) => `<p>${p}</p>`).join(''));
    }
    emit();
  };

  const addLink = () => {
    saveSelection();
    const url = window.prompt(
      'Link address\n\nUse https://… for another website, or /pages/… or /shop for a page on this site.',
      'https://'
    );
    restoreSelection();
    if (!url || url === 'https://') return;
    if (!/^(https?:\/\/|\/|mailto:|tel:)/i.test(url.trim())) {
      window.alert('Links must start with https://, /, mailto: or tel:');
      return;
    }
    const sel = window.getSelection();
    if (sel && !sel.isCollapsed) {
      exec('createLink', url.trim());
    } else {
      exec('insertHTML', `<a href="${escapeHtml(url.trim())}">${escapeHtml(url.trim())}</a>`);
    }
  };

  const pickImage = () => {
    saveSelection();
    setUploadError(null);
    fileRef.current?.click();
  };

  const uploadImage = async (e) => {
    const file = e.target.files?.[0];
    e.target.value = '';
    if (!file) return;
    setUploading(true);
    setUploadError(null);
    try {
      const fd = new FormData();
      fd.append('image', file);
      const { url } = await api.upload('/admin/uploads', fd);
      restoreSelection();
      exec('insertHTML', `<p><img src="${escapeHtml(url)}" alt=""></p>`);
    } catch (err) {
      setUploadError(err.message);
    } finally {
      setUploading(false);
    }
  };

  const locked = mode !== 'visual';

  return (
    <div className="mt-1.5 overflow-hidden rounded-lg border border-slate-300 focus-within:border-brand-500 focus-within:ring-2 focus-within:ring-brand-100">
      <div className="flex flex-wrap items-center gap-0.5 border-b border-slate-200 bg-slate-50 px-2 py-1.5">
        <select
          aria-label="Text style"
          disabled={mode !== 'visual'}
          value={BLOCKS.some((b) => b.value === active.block) ? active.block : 'p'}
          onMouseDown={saveSelection}
          onChange={(e) => {
            restoreSelection();
            exec('formatBlock', `<${e.target.value}>`);
          }}
          className="mr-1 h-8 rounded-md border border-slate-200 bg-white px-2 text-xs text-slate-700 focus:outline-none disabled:opacity-40"
        >
          {BLOCKS.map((b) => <option key={b.value} value={b.value}>{b.label}</option>)}
        </select>

        <ToolbarButton locked={locked} label="Bold" isActive={active.bold} onClick={() => exec('bold')}>
          <span className="font-bold">B</span>
        </ToolbarButton>
        <ToolbarButton locked={locked} label="Italic" isActive={active.italic} onClick={() => exec('italic')}>
          <span className="italic">I</span>
        </ToolbarButton>
        <ToolbarButton locked={locked} label="Underline" isActive={active.underline} onClick={() => exec('underline')}>
          <span className="underline">U</span>
        </ToolbarButton>

        <span className="mx-1 h-5 w-px bg-slate-200" />
        <ToolbarButton locked={locked} label="Bulleted list" isActive={active.ul} onClick={() => toggleList('insertUnorderedList')}>
          <Icon d={ICONS.ul} />
        </ToolbarButton>
        <ToolbarButton locked={locked} label="Numbered list" isActive={active.ol} onClick={() => toggleList('insertOrderedList')}>
          <Icon d={ICONS.ol} />
        </ToolbarButton>

        <span className="mx-1 h-5 w-px bg-slate-200" />
        <ToolbarButton locked={locked} label="Add link" onClick={addLink}><Icon d={ICONS.link} /></ToolbarButton>
        <ToolbarButton locked={locked} label="Remove link" onClick={() => exec('unlink')}><Icon d={ICONS.unlink} /></ToolbarButton>
        <ToolbarButton locked={locked} label={uploading ? 'Uploading image…' : 'Insert image'} onClick={pickImage} disabled={uploading}>
          {uploading ? <span className="text-[11px]">…</span> : <Icon d={ICONS.image} />}
        </ToolbarButton>
        <ToolbarButton locked={locked} label="Divider line" onClick={() => exec('insertHorizontalRule')}><Icon d={ICONS.hr} /></ToolbarButton>

        <span className="mx-1 h-5 w-px bg-slate-200" />
        <ToolbarButton locked={locked} label="Clear formatting" onClick={() => exec('removeFormat')}><Icon d={ICONS.clear} /></ToolbarButton>
        <ToolbarButton locked={locked} label="Undo" onClick={() => exec('undo')}><Icon d={ICONS.undo} /></ToolbarButton>
        <ToolbarButton locked={locked} label="Redo" onClick={() => exec('redo')}><Icon d={ICONS.redo} /></ToolbarButton>

        <button
          type="button"
          onClick={() => setMode((m) => (m === 'visual' ? 'html' : 'visual'))}
          className={`ml-auto h-8 rounded-md px-2.5 text-xs font-semibold transition ${
            mode === 'html' ? 'bg-slate-800 text-white' : 'text-slate-600 hover:bg-slate-100'
          }`}
          title="Edit the raw HTML"
        >
          {mode === 'html' ? 'Back to editor' : 'HTML'}
        </button>

        <input ref={fileRef} type="file" accept="image/jpeg,image/png,image/webp,image/gif"
               className="hidden" onChange={uploadImage} />
      </div>

      {uploadError && (
        <p className="border-b border-rose-100 bg-rose-50 px-3 py-2 text-xs text-rose-700">{uploadError}</p>
      )}

      <div
        ref={editorRef}
        contentEditable
        suppressContentEditableWarning
        role="textbox"
        aria-multiline="true"
        aria-label="Page content"
        data-placeholder={placeholder}
        onInput={emit}
        onBlur={emit}
        onFocus={onFocus}
        onPaste={onPaste}
        onKeyUp={refreshActive}
        onMouseUp={refreshActive}
        className={`rich-content rte-surface min-h-[22rem] bg-white px-4 py-3 focus:outline-none ${
          mode === 'visual' ? '' : 'hidden'
        }`}
      />

      {mode === 'html' && (
        <textarea
          value={value}
          onChange={(e) => onChange(e.target.value)}
          spellCheck={false}
          aria-label="Page HTML"
          className="block min-h-[22rem] w-full resize-y border-0 bg-slate-900 p-4 font-mono text-xs leading-relaxed text-slate-100 focus:outline-none"
        />
      )}
    </div>
  );
}
