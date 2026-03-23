'use strict';

const canvas = document.getElementById('canvas');
const ctx = canvas.getContext('2d');

const instrumentSelect = document.getElementById('instrumentSelect');
const annotationTypeSelect = document.getElementById('annotationTypeSelect');
const outputNameHint = document.getElementById('outputNameHint');

const imageFileInput = document.getElementById('imageFile');
const uploadImageBtn = document.getElementById('uploadImageBtn');

const answersRegionSection = document.getElementById('answersRegionSection');
const answersRegionDisabled = document.getElementById('answersRegionDisabled');
const answersRegionSelect = document.getElementById('answersRegionSelect');
const newAnswersRegionInput = document.getElementById('newAnswersRegionInput');
const addAnswersRegionBtn = document.getElementById('addAnswersRegionBtn');

const markSectionRegionBtn = document.getElementById('markSectionRegionBtn');
const clearSectionRegionBtn = document.getElementById('clearSectionRegionBtn');
const markPointsRegionBtn = document.getElementById('markPointsRegionBtn');
const clearPointsRegionBtn = document.getElementById('clearPointsRegionBtn');

const gridRowsInput = document.getElementById('gridRows');
const gridColsInput = document.getElementById('gridCols');
const generateGridBtn = document.getElementById('generateGridBtn');

const labelInput = document.getElementById('labelInput');
const saveBtn = document.getElementById('saveBtn');
const loadBtn = document.getElementById('loadBtn');
const fitBtn = document.getElementById('fitBtn');
const resetBtn = document.getElementById('resetBtn');

let image = null;
let imageMeta = { width: 0, height: 0 };

let zoom = 1;
let panX = 0;
let panY = 0;

let spaceDown = false;
let isPanning = false;
let panAnchorX = 0;
let panAnchorY = 0;

let selectedPoint = null;
let isDraggingPoint = false;

let activeRegionMode = null; // 'section' | 'points' | null
let isDrawingRegion = false;
let regionStart = null;
let regionCurrent = null;

let currentLabel = 'C1R0';

const state = {
  instrument: 'gri',
  annotationType: 'folio',
  folio: {
    sectionRegion: null,
    pointsRegion: null,
    points: {},
  },
  answers: {
    activeBlock: 'answers_block_1',
    blocks: {
      answers_block_1: {
        sectionRegion: null,
        pointsRegion: null,
        points: {},
      },
    },
  },
};

const COLORS = {
  bg: '#0f1115',
  pointFolio: '#4ecdc4',
  pointAnswers: '#ffd166',
  sectionRegion: '#8dd3ff',
  pointsRegion: '#7fc8a8',
};

function getTemplateName() {
  return `${state.instrument}-${state.annotationType}-annotator`;
}

function setStatus(message) {
  document.getElementById('status').textContent = message;
}

function setGridStatus(message) {
  document.getElementById('gridStatus').textContent = message;
}

function setRegionStatus(message) {
  document.getElementById('regionStatus').textContent = message;
}

function updateOutputNameHint() {
  outputNameHint.textContent = `Archivo: ${getTemplateName()}.json`;
}

function resizeCanvas() {
  const area = canvas.parentElement;
  canvas.width = area.clientWidth;
  canvas.height = area.clientHeight;
  draw();
}

function canvasToImage(cx, cy) {
  return {
    x: Math.round((cx - panX) / zoom),
    y: Math.round((cy - panY) / zoom),
  };
}

function imageToCanvas(ix, iy) {
  return {
    x: ix * zoom + panX,
    y: iy * zoom + panY,
  };
}

function getMousePos(event) {
  const rect = canvas.getBoundingClientRect();
  return {
    x: event.clientX - rect.left,
    y: event.clientY - rect.top,
  };
}

function normalizeRegionFromCorners(a, b) {
  const x1 = Math.min(a.x, b.x);
  const y1 = Math.min(a.y, b.y);
  const x2 = Math.max(a.x, b.x);
  const y2 = Math.max(a.y, b.y);

  return {
    x: Math.round(x1),
    y: Math.round(y1),
    w: Math.round(Math.max(0, x2 - x1)),
    h: Math.round(Math.max(0, y2 - y1)),
  };
}

function clampPointToRegion(point, region) {
  return {
    x: Math.min(Math.max(point.x, region.x), region.x + region.w),
    y: Math.min(Math.max(point.y, region.y), region.y + region.h),
  };
}

function ensureAnswersBlock(name) {
  if (!name || !name.trim()) {
    return null;
  }

  const block = name.trim();
  if (!state.answers.blocks[block]) {
    state.answers.blocks[block] = {
      sectionRegion: null,
      pointsRegion: null,
      points: {},
    };
  }

  return block;
}

function getActiveTarget() {
  if (state.annotationType === 'folio') {
    return state.folio;
  }

  const block = ensureAnswersBlock(state.answers.activeBlock);
  state.answers.activeBlock = block;
  return state.answers.blocks[block];
}

function updateAnswersBlockSelect() {
  if (state.annotationType !== 'answers') {
    return;
  }

  const names = Object.keys(state.answers.blocks);
  if (!names.length) {
    ensureAnswersBlock('answers_block_1');
  }

  const keys = Object.keys(state.answers.blocks);
  if (!state.answers.blocks[state.answers.activeBlock]) {
    state.answers.activeBlock = keys[0];
  }

  answersRegionSelect.innerHTML = keys
    .map((name) => `<option value="${name}">${name}</option>`)
    .join('');

  answersRegionSelect.value = state.answers.activeBlock;
}

function syncUi() {
  instrumentSelect.value = state.instrument;
  annotationTypeSelect.value = state.annotationType;

  if (state.annotationType === 'answers') {
    answersRegionSection.classList.remove('hidden');
    answersRegionDisabled.classList.add('hidden');
    updateAnswersBlockSelect();
  } else {
    answersRegionSection.classList.add('hidden');
    answersRegionDisabled.classList.remove('hidden');
  }

  updateOutputNameHint();
  draw();
}

function getPointGroups() {
  if (state.annotationType === 'folio') {
    return [{ name: 'folio', target: state.folio }];
  }

  return Object.entries(state.answers.blocks).map(([name, target]) => ({
    name,
    target,
  }));
}

function getPointColor(groupName) {
  return groupName === 'folio' ? COLORS.pointFolio : COLORS.pointAnswers;
}

function drawRegion(region, color, label, dashed = false) {
  if (!region || region.w <= 0 || region.h <= 0) {
    return;
  }

  const p1 = imageToCanvas(region.x, region.y);
  const p2 = imageToCanvas(region.x + region.w, region.y + region.h);

  ctx.save();
  if (dashed) {
    ctx.setLineDash([6, 4]);
  }

  ctx.strokeStyle = color;
  ctx.lineWidth = 2;
  ctx.strokeRect(p1.x, p1.y, p2.x - p1.x, p2.y - p1.y);

  ctx.fillStyle = `${color}22`;
  ctx.fillRect(p1.x, p1.y, p2.x - p1.x, p2.y - p1.y);

  if (label) {
    ctx.fillStyle = color;
    ctx.font = 'bold 11px monospace';
    ctx.fillText(label, p1.x + 4, p1.y - 6);
  }

  ctx.restore();
}

function drawPoint(groupName, label, point, selected) {
  const cp = imageToCanvas(point.x, point.y);

  ctx.beginPath();
  ctx.arc(cp.x, cp.y, selected ? 7 : 4, 0, Math.PI * 2);
  ctx.fillStyle = selected ? '#ff6b6b' : getPointColor(groupName);
  ctx.globalAlpha = 0.9;
  ctx.fill();
  ctx.globalAlpha = 1;
  ctx.lineWidth = 1;
  ctx.strokeStyle = 'rgba(255,255,255,0.9)';
  ctx.stroke();

  ctx.fillStyle = '#ffffff';
  ctx.font = '11px monospace';
  ctx.fillText(label, cp.x + 8, cp.y - 3);
}

function drawBackground() {
  ctx.clearRect(0, 0, canvas.width, canvas.height);
  ctx.fillStyle = COLORS.bg;
  ctx.fillRect(0, 0, canvas.width, canvas.height);

  if (!image) {
    ctx.fillStyle = '#6b7280';
    ctx.font = '16px monospace';
    ctx.textAlign = 'center';
    ctx.fillText('Sube una imagen para comenzar', canvas.width / 2, canvas.height / 2);
    ctx.textAlign = 'left';
    return;
  }

  ctx.drawImage(image, panX, panY, image.naturalWidth * zoom, image.naturalHeight * zoom);
}

function drawPreviewRegion() {
  if (!isDrawingRegion || !regionStart || !regionCurrent || !activeRegionMode) {
    return;
  }

  const region = normalizeRegionFromCorners(regionStart, regionCurrent);
  const label = activeRegionMode === 'section' ? 'seccion (preview)' : 'puntos (preview)';
  drawRegion(region, '#f97316', label, true);
}

function updateSummary() {
  const summary = document.getElementById('regionSummary');

  if (state.annotationType === 'folio') {
    const sectionState = state.folio.sectionRegion ? 'ok' : 'sin region';
    const pointsState = state.folio.pointsRegion ? 'ok' : 'sin region';
    const pointsCount = Object.keys(state.folio.points).length;

    summary.innerHTML = `
      <div><span>Modo</span><span class="badge">Folio</span></div>
      <div><span>Region seccion</span><span class="badge">${sectionState}</span></div>
      <div><span>Region puntos</span><span class="badge">${pointsState}</span></div>
      <div><span>Puntos</span><span class="badge">${pointsCount}</span></div>
    `;
    return;
  }

  const lines = Object.entries(state.answers.blocks).map(([name, target]) => {
    const sectionState = target.sectionRegion ? 'S' : '-';
    const pointsState = target.pointsRegion ? 'P' : '-';
    const pointsCount = Object.keys(target.points).length;
    return `<div><span>${name} (${sectionState}/${pointsState})</span><span class="badge">${pointsCount}</span></div>`;
  });

  summary.innerHTML = lines.length ? lines.join('') : '<p class="muted">Sin bloques de respuestas.</p>';
}

function draw() {
  drawBackground();

  for (const group of getPointGroups()) {
    drawRegion(group.target.sectionRegion, COLORS.sectionRegion, `${group.name}: seccion`);
    drawRegion(group.target.pointsRegion, COLORS.pointsRegion, `${group.name}: puntos`);

    for (const [label, point] of Object.entries(group.target.points)) {
      const selected =
        selectedPoint &&
        selectedPoint.groupName === group.name &&
        selectedPoint.label === label;

      drawPoint(group.name, label, point, selected);
    }
  }

  drawPreviewRegion();
  updateSummary();
}

function findNearestPoint(cx, cy, threshold = 10) {
  let best = null;
  let bestDistance = Infinity;

  for (const group of getPointGroups()) {
    for (const [label, point] of Object.entries(group.target.points)) {
      const cp = imageToCanvas(point.x, point.y);
      const dist = Math.hypot(cp.x - cx, cp.y - cy);

      if (dist < threshold && dist < bestDistance) {
        best = { groupName: group.name, label };
        bestDistance = dist;
      }
    }
  }

  return best;
}

function setActiveRegionMode(mode) {
  activeRegionMode = mode;
  isDrawingRegion = false;
  regionStart = null;
  regionCurrent = null;

  markSectionRegionBtn.classList.toggle('active', mode === 'section');
  markPointsRegionBtn.classList.toggle('active', mode === 'points');

  if (mode === 'section') {
    setRegionStatus('Marca la region de seccion con click y arrastre.');
    setStatus('Modo region de seccion activo.');
  } else if (mode === 'points') {
    setRegionStatus('Marca la region de puntos (donde se autogenera el grid).');
    setStatus('Modo region de puntos activo.');
  } else {
    setRegionStatus('');
  }
}

function clearRegion(kind) {
  const target = getActiveTarget();

  if (kind === 'section') {
    target.sectionRegion = null;
    target.pointsRegion = null;
    target.points = {};
    setStatus('Region de seccion limpiada (tambien se limpio la region de puntos y el grid).');
  } else {
    target.pointsRegion = null;
    target.points = {};
    setStatus('Region de puntos limpiada (tambien se limpio el grid).');
  }

  selectedPoint = null;
  setGridStatus('');
  draw();
}

function generateGridInsidePointsRegion() {
  const target = getActiveTarget();

  if (!target.sectionRegion) {
    setStatus('Primero define la region de seccion.');
    return;
  }

  const baseRegion = target.pointsRegion || target.sectionRegion;
  if (!baseRegion) {
    setStatus('Define una region valida para generar grid.');
    return;
  }

  const rows = Number.parseInt(gridRowsInput.value, 10) || 10;
  const cols = Number.parseInt(gridColsInput.value, 10) || 11;

  const left = baseRegion.x;
  const top = baseRegion.y;
  const right = baseRegion.x + baseRegion.w;
  const bottom = baseRegion.y + baseRegion.h;

  target.points = {};

  for (let col = 1; col <= cols; col += 1) {
    for (let row = 0; row < rows; row += 1) {
      const tx = cols > 1 ? (col - 1) / (cols - 1) : 0.5;
      const ty = rows > 1 ? row / (rows - 1) : 0.5;

      target.points[`C${col}R${row}`] = {
        x: Math.round(left + tx * (right - left)),
        y: Math.round(top + ty * (bottom - top)),
      };
    }
  }

  const activeName = state.annotationType === 'folio' ? 'folio' : state.answers.activeBlock;
  setGridStatus(`Grid ${rows}x${cols} generado en ${activeName}.`);
  setStatus(`Grid ${rows}x${cols} generado correctamente.`);
  draw();
}

function toRelativePoint(point, originRegion) {
  return {
    x: Math.round(point.x - originRegion.x),
    y: Math.round(point.y - originRegion.y),
  };
}

function toRelativeRegion(region, originRegion) {
  if (!region) {
    return null;
  }

  return {
    x: Math.round(region.x - originRegion.x),
    y: Math.round(region.y - originRegion.y),
    w: Math.round(region.w),
    h: Math.round(region.h),
  };
}

function toAbsolutePoint(point, originRegion) {
  return {
    x: Math.round(point.x + originRegion.x),
    y: Math.round(point.y + originRegion.y),
  };
}

function toAbsoluteRegion(region, originRegion) {
  if (!region) {
    return null;
  }

  return {
    x: Math.round(region.x + originRegion.x),
    y: Math.round(region.y + originRegion.y),
    w: Math.round(region.w),
    h: Math.round(region.h),
  };
}

function looksRelativeToRegion(points, region) {
  if (!region) {
    return false;
  }

  const values = Object.values(points);
  if (!values.length) {
    return false;
  }

  return values.every((point) => (
    point.x >= 0 && point.y >= 0 && point.x <= region.w + 2 && point.y <= region.h + 2
  ));
}

function calculateColumnsFromRelativeGrid(points) {
  const columnsMap = {};

  for (const [label, point] of Object.entries(points)) {
    const match = label.match(/^C(\d+)R(\d+)$/i);
    if (!match) {
      continue;
    }

    const col = Number.parseInt(match[1], 10);
    const row = Number.parseInt(match[2], 10);

    if (!columnsMap[col]) {
      columnsMap[col] = {};
    }

    columnsMap[col][row] = point;
  }

  const entries = Object.entries(columnsMap)
    .map(([col, rowMap]) => {
      const rows = Object.keys(rowMap)
        .map((v) => Number.parseInt(v, 10))
        .sort((a, b) => a - b);

      if (!rows.length) {
        return null;
      }

      const minRow = rows[0];
      const maxRow = rows[rows.length - 1];
      const midRow = rows[Math.floor((rows.length - 1) / 2)];

      return [
        `C${col}`,
        {
          [`r${minRow}`]: rowMap[minRow],
          [`r${midRow}`]: rowMap[midRow],
          [`r${maxRow}`]: rowMap[maxRow],
        },
      ];
    })
    .filter(Boolean)
    .sort((a, b) => Number.parseInt(a[0].slice(1), 10) - Number.parseInt(b[0].slice(1), 10));

  return Object.fromEntries(entries);
}

function serializeTarget(target) {
  const baseRegion = target.sectionRegion || target.pointsRegion;
  if (!baseRegion) {
    return {
      region: null,
      points_region: null,
      columns: {},
      grid_points: {},
    };
  }

  const relativePoints = Object.fromEntries(
    Object.entries(target.points).map(([label, point]) => [label, toRelativePoint(point, baseRegion)]),
  );

  return {
    region: {
      x: Math.round(baseRegion.x),
      y: Math.round(baseRegion.y),
      w: Math.round(baseRegion.w),
      h: Math.round(baseRegion.h),
    },
    points_region: toRelativeRegion(target.pointsRegion || baseRegion, baseRegion),
    columns: calculateColumnsFromRelativeGrid(relativePoints),
    grid_points: relativePoints,
  };
}

function buildAnnotationData() {
  const meta = {
    instrument: state.instrument,
    annotation_type: state.annotationType,
    width: imageMeta.width,
    height: imageMeta.height,
  };

  if (state.annotationType === 'folio') {
    return {
      meta,
      folio_annotation: serializeTarget(state.folio),
    };
  }

  const answers = {};
  for (const [name, target] of Object.entries(state.answers.blocks)) {
    answers[name] = serializeTarget(target);
  }

  return {
    meta,
    answers_annotation: answers,
  };
}

function hydrateTargetFromAnnotation(target, annotation) {
  const sectionRegion = annotation.region || null;
  const pointsRegionRaw = annotation.points_region || null;
  const rawPoints = annotation.grid_points || {};

  target.sectionRegion = sectionRegion;

  if (sectionRegion && pointsRegionRaw) {
    const isRelativePointsRegion = (
      pointsRegionRaw.x >= 0 &&
      pointsRegionRaw.y >= 0 &&
      pointsRegionRaw.x + pointsRegionRaw.w <= sectionRegion.w + 2 &&
      pointsRegionRaw.y + pointsRegionRaw.h <= sectionRegion.h + 2
    );

    target.pointsRegion = isRelativePointsRegion
      ? toAbsoluteRegion(pointsRegionRaw, sectionRegion)
      : pointsRegionRaw;
  } else {
    target.pointsRegion = null;
  }

  if (sectionRegion && looksRelativeToRegion(rawPoints, sectionRegion)) {
    target.points = Object.fromEntries(
      Object.entries(rawPoints).map(([label, point]) => [label, toAbsolutePoint(point, sectionRegion)]),
    );
  } else {
    target.points = { ...rawPoints };
  }
}

function hydrateFromCurrentFormat(data) {
  if (!data) {
    return false;
  }

  let loaded = false;

  if (data.folio_annotation) {
    hydrateTargetFromAnnotation(state.folio, data.folio_annotation);
    state.annotationType = 'folio';
    loaded = true;
  }

  if (data.answers_annotation) {
    state.answers.blocks = {};

    for (const [name, annotation] of Object.entries(data.answers_annotation)) {
      const block = ensureAnswersBlock(name);
      hydrateTargetFromAnnotation(state.answers.blocks[block], annotation);
    }

    if (!Object.keys(state.answers.blocks).length) {
      ensureAnswersBlock('answers_block_1');
    }

    state.answers.activeBlock = Object.keys(state.answers.blocks)[0];
    state.annotationType = 'answers';
    loaded = true;
  }

  if (data.meta?.instrument) {
    state.instrument = data.meta.instrument;
  }

  if (data.meta?.width && data.meta?.height) {
    imageMeta = {
      width: data.meta.width,
      height: data.meta.height,
    };
  }

  return loaded;
}

function hydrateFromLegacy(data) {
  if (!data || !data.regions) {
    return false;
  }

  if (data.regions.folio) {
    state.folio.points = data.regions.folio.points || {};
  }

  for (const [name, regionData] of Object.entries(data.regions)) {
    if (name === 'folio') {
      continue;
    }

    const block = ensureAnswersBlock(name);
    state.answers.blocks[block].points = regionData.points || {};
  }

  if (data.meta?.width && data.meta?.height) {
    imageMeta = {
      width: data.meta.width,
      height: data.meta.height,
    };
  }

  return true;
}

async function saveAnnotation() {
  const payload = {
    template_name: getTemplateName(),
    data: buildAnnotationData(),
  };

  try {
    const response = await fetch('/save', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });

    const data = await response.json();
    if (!response.ok) {
      setStatus(`Error guardando: ${data.error || 'respuesta invalida'}`);
      return;
    }

    setStatus(`Guardado: ${data.saved}`);
  } catch (error) {
    setStatus(`Error guardando: ${error}`);
  }
}

async function loadAnnotation() {
  const templateName = getTemplateName();

  try {
    const response = await fetch(`/load/${encodeURIComponent(templateName)}`);
    const data = await response.json();

    if (!Object.keys(data || {}).length) {
      setStatus(`No existe anotacion para ${templateName}.json`);
      return;
    }

    const currentLoaded = hydrateFromCurrentFormat(data);
    const legacyLoaded = currentLoaded ? false : hydrateFromLegacy(data);

    if (!currentLoaded && !legacyLoaded) {
      setStatus('Formato no reconocido para cargar.');
      return;
    }

    selectedPoint = null;
    setActiveRegionMode(null);
    syncUi();
    setStatus(`Cargado: ${templateName}.json`);
  } catch (error) {
    setStatus(`Error cargando: ${error}`);
  }
}

function fitToScreen() {
  if (!image) {
    return;
  }

  const scaleX = canvas.width / image.naturalWidth;
  const scaleY = canvas.height / image.naturalHeight;
  zoom = Math.min(scaleX, scaleY) * 0.95;
  panX = (canvas.width - image.naturalWidth * zoom) / 2;
  panY = (canvas.height - image.naturalHeight * zoom) / 2;
  draw();
}

function resetZoom() {
  zoom = 1;
  panX = 0;
  panY = 0;
  draw();
}

function loadImageFromServer(filename) {
  return new Promise((resolve, reject) => {
    const img = new Image();
    img.src = `/image/${encodeURIComponent(filename)}`;

    img.onload = () => {
      image = img;
      imageMeta = {
        width: img.naturalWidth,
        height: img.naturalHeight,
      };
      fitToScreen();
      setStatus(`Imagen cargada: ${filename} (${img.naturalWidth}x${img.naturalHeight})`);
      resolve();
    };

    img.onerror = () => {
      reject(new Error('No se pudo cargar la imagen del servidor.'));
    };
  });
}

async function uploadImage() {
  if (!imageFileInput.files.length) {
    setStatus('Selecciona una imagen primero.');
    return;
  }

  const payload = new FormData();
  payload.append('image', imageFileInput.files[0]);

  try {
    const response = await fetch('/upload', {
      method: 'POST',
      body: payload,
    });

    const data = await response.json();
    if (!response.ok || !data.filename) {
      setStatus(`Error subiendo imagen: ${data.error || 'respuesta invalida'}`);
      return;
    }

    await loadImageFromServer(data.filename);
  } catch (error) {
    setStatus(`Error subiendo imagen: ${error}`);
  }
}

canvas.addEventListener('mousedown', (event) => {
  const pos = getMousePos(event);

  if (event.button === 1 || (event.button === 0 && spaceDown)) {
    event.preventDefault();
    isPanning = true;
    panAnchorX = event.clientX - panX;
    panAnchorY = event.clientY - panY;
    canvas.style.cursor = 'grab';
    return;
  }

  if (event.button !== 0) {
    return;
  }

  if (activeRegionMode) {
    isDrawingRegion = true;
    regionStart = canvasToImage(pos.x, pos.y);
    regionCurrent = regionStart;
    draw();
    return;
  }

  const nearest = findNearestPoint(pos.x, pos.y);
  if (nearest) {
    selectedPoint = nearest;
    isDraggingPoint = true;
    draw();
    return;
  }

  const target = getActiveTarget();
  const point = canvasToImage(pos.x, pos.y);

  if (target.sectionRegion) {
    const bounded = clampPointToRegion(point, target.sectionRegion);
    target.points[currentLabel] = bounded;
  } else {
    target.points[currentLabel] = point;
  }

  const groupName = state.annotationType === 'folio' ? 'folio' : state.answers.activeBlock;
  selectedPoint = { groupName, label: currentLabel };
  isDraggingPoint = true;
  draw();
});

canvas.addEventListener('mousemove', (event) => {
  if (isPanning) {
    panX = event.clientX - panAnchorX;
    panY = event.clientY - panAnchorY;
    draw();
    return;
  }

  const pos = getMousePos(event);

  if (isDrawingRegion) {
    regionCurrent = canvasToImage(pos.x, pos.y);
    draw();
    return;
  }

  if (isDraggingPoint && selectedPoint) {
    const target = selectedPoint.groupName === 'folio'
      ? state.folio
      : state.answers.blocks[selectedPoint.groupName];

    if (!target || !target.points[selectedPoint.label]) {
      return;
    }

    let nextPoint = canvasToImage(pos.x, pos.y);
    if (target.sectionRegion) {
      nextPoint = clampPointToRegion(nextPoint, target.sectionRegion);
    }

    target.points[selectedPoint.label] = nextPoint;
    draw();
  }
});

canvas.addEventListener('mouseup', () => {
  if (isDrawingRegion && regionStart && regionCurrent && activeRegionMode) {
    const target = getActiveTarget();
    const region = normalizeRegionFromCorners(regionStart, regionCurrent);

    if (activeRegionMode === 'section') {
      target.sectionRegion = region;

      if (target.pointsRegion) {
        target.pointsRegion = {
          x: Math.max(region.x, target.pointsRegion.x),
          y: Math.max(region.y, target.pointsRegion.y),
          w: Math.max(0, Math.min(region.x + region.w, target.pointsRegion.x + target.pointsRegion.w) - Math.max(region.x, target.pointsRegion.x)),
          h: Math.max(0, Math.min(region.y + region.h, target.pointsRegion.y + target.pointsRegion.h) - Math.max(region.y, target.pointsRegion.y)),
        };
      }

      for (const [label, point] of Object.entries(target.points)) {
        target.points[label] = clampPointToRegion(point, region);
      }

      setStatus('Region de seccion guardada.');
    } else {
      if (!target.sectionRegion) {
        setStatus('Primero define la region de seccion.');
      } else {
        const constrained = {
          x: Math.max(target.sectionRegion.x, region.x),
          y: Math.max(target.sectionRegion.y, region.y),
          w: Math.max(0, Math.min(target.sectionRegion.x + target.sectionRegion.w, region.x + region.w) - Math.max(target.sectionRegion.x, region.x)),
          h: Math.max(0, Math.min(target.sectionRegion.y + target.sectionRegion.h, region.y + region.h) - Math.max(target.sectionRegion.y, region.y)),
        };

        target.pointsRegion = constrained;
        setStatus('Region de puntos guardada.');
      }
    }

    draw();
  }

  isDrawingRegion = false;
  isDraggingPoint = false;
  isPanning = false;

  if (!spaceDown) {
    canvas.style.cursor = 'crosshair';
  }
});

canvas.addEventListener('wheel', (event) => {
  event.preventDefault();

  if (event.ctrlKey) {
    const pos = getMousePos(event);
    const anchor = canvasToImage(pos.x, pos.y);
    const factor = event.deltaY < 0 ? 1.08 : 0.92;

    zoom = Math.min(Math.max(zoom * factor, 0.05), 30);
    panX = pos.x - anchor.x * zoom;
    panY = pos.y - anchor.y * zoom;
  } else {
    panX -= event.deltaX;
    panY -= event.deltaY;
  }

  draw();
}, { passive: false });

canvas.addEventListener('contextmenu', (event) => event.preventDefault());

document.addEventListener('keydown', (event) => {
  if (event.target.matches('input, textarea, select')) {
    return;
  }

  if (event.code === 'Space') {
    event.preventDefault();
    spaceDown = true;
    canvas.style.cursor = 'grab';
  }

  if ((event.code === 'Delete' || event.code === 'Backspace') && selectedPoint) {
    const target = selectedPoint.groupName === 'folio'
      ? state.folio
      : state.answers.blocks[selectedPoint.groupName];

    if (target) {
      delete target.points[selectedPoint.label];
      selectedPoint = null;
      draw();
    }
  }

  if (event.code === 'Escape') {
    setActiveRegionMode(null);
    selectedPoint = null;
    draw();
  }
});

document.addEventListener('keyup', (event) => {
  if (event.code === 'Space') {
    spaceDown = false;
    canvas.style.cursor = 'crosshair';
  }
});

instrumentSelect.addEventListener('change', (event) => {
  state.instrument = event.target.value;
  updateOutputNameHint();
});

annotationTypeSelect.addEventListener('change', (event) => {
  state.annotationType = event.target.value;
  selectedPoint = null;
  setActiveRegionMode(null);
  syncUi();
});

answersRegionSelect.addEventListener('change', (event) => {
  state.answers.activeBlock = event.target.value;
  selectedPoint = null;
  draw();
});

addAnswersRegionBtn.addEventListener('click', () => {
  const block = ensureAnswersBlock(newAnswersRegionInput.value);
  if (!block) {
    setStatus('Escribe un nombre valido para el bloque de respuestas.');
    return;
  }

  state.answers.activeBlock = block;
  newAnswersRegionInput.value = '';
  updateAnswersBlockSelect();
  draw();
  setStatus(`Bloque '${block}' creado.`);
});

markSectionRegionBtn.addEventListener('click', () => {
  setActiveRegionMode(activeRegionMode === 'section' ? null : 'section');
});

markPointsRegionBtn.addEventListener('click', () => {
  setActiveRegionMode(activeRegionMode === 'points' ? null : 'points');
});

clearSectionRegionBtn.addEventListener('click', () => clearRegion('section'));
clearPointsRegionBtn.addEventListener('click', () => clearRegion('points'));

generateGridBtn.addEventListener('click', generateGridInsidePointsRegion);

labelInput.addEventListener('input', (event) => {
  currentLabel = event.target.value.trim() || 'C1R0';
});

uploadImageBtn.addEventListener('click', uploadImage);
saveBtn.addEventListener('click', saveAnnotation);
loadBtn.addEventListener('click', loadAnnotation);
fitBtn.addEventListener('click', fitToScreen);
resetBtn.addEventListener('click', resetZoom);

window.addEventListener('resize', resizeCanvas);

syncUi();
resizeCanvas();
setStatus('Listo.');