#!/usr/bin/env python3
"""
Web-based calibration tool to help identify bubble positions in OMR templates.
This script runs a Flask server that displays an image in the browser for coordinate selection.
"""

from flask import Flask, render_template_string, jsonify, request
import cv2
import base64
import sys
import os
import unicodedata

app = Flask(__name__)

# Global variables
coordinates = []
image_path = None
image_data = None
image_width = 0
image_height = 0
current_section = "custom"  # folio, referencia-i, referencia-iii, referencia-v, custom
current_question = 1

HTML_TEMPLATE = """
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calibración de Burbujas OMR</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #1a1a2e;
            color: #eee;
            padding: 20px;
        }
        .container {
            max-width: 1800px;
            margin: 0 auto;
        }
        h1 {
            text-align: center;
            color: #16c79a;
            margin-bottom: 20px;
            font-size: 2em;
        }
        .instructions {
            background: #16213e;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #16c79a;
        }
        .instructions h2 {
            color: #16c79a;
            margin-bottom: 10px;
            font-size: 1.3em;
        }
        .instructions ul {
            list-style-position: inside;
            line-height: 1.8;
        }
        .controls {
            background: #16213e;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }
        .controls-sticky {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: #16213e;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            border: 2px solid #16c79a;
        }
        button {
            background: #16c79a;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            font-weight: bold;
            transition: all 0.3s;
        }
        button:hover {
            background: #13a87e;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(22, 199, 154, 0.3);
        }
        button:active {
            transform: translateY(0);
        }
        .info {
            flex: 1;
            min-width: 200px;
            background: #0f3460;
            padding: 10px 15px;
            border-radius: 5px;
        }
        .canvas-container {
            background: #16213e;
            padding: 20px;
            border-radius: 10px;
            overflow: auto;
            text-align: center;
        }
        canvas {
            border: 2px solid #16c79a;
            cursor: crosshair;
            background: #fff;
            max-width: 100%;
            height: auto;
        }
        .coordinates-list {
            background: #16213e;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            max-height: 400px;
            overflow-y: auto;
        }
        .coordinates-list h2 {
            color: #16c79a;
            margin-bottom: 15px;
        }
        .coord-item {
            background: #0f3460;
            padding: 10px;
            margin-bottom: 8px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            border-left: 3px solid #16c79a;
        }
        .bubble-complete {
            background: #1a472a;
            border-left: 3px solid #16c79a;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .stat-card {
            background: #16213e;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            border-top: 3px solid #16c79a;
        }
        .stat-value {
            font-size: 2em;
            color: #16c79a;
            font-weight: bold;
        }
        .stat-label {
            color: #aaa;
            margin-top: 5px;
        }
        .mouse-pos {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #16213e;
            padding: 12px 18px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            border: 2px solid #16c79a;
            z-index: 1001;
            font-size: 1.1em;
            font-weight: bold;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎯 Calibración de Burbujas OMR</h1>
        
        <div class="instructions">
            <h2>📋 Instrucciones - Modo Centro</h2>
            <ul>
                <li>Haz clic en el <strong>centro de cada burbuja</strong></li>
                <li>Se dibujará automáticamente un cuadrado del tamaño configurado</li>
                <li>Ajusta el tamaño de la burbuja con el control deslizante</li>
                <li>Las coordenadas se calcularán automáticamente (x, y, ancho, alto)</li>
                <li>Usa "Deshacer" si necesitas corregir un click</li>
            </ul>
        </div>

        <div class="controls">
            <button onclick="resetCoordinates()">🔄 Reiniciar Coordenadas</button>
            <button onclick="undoLast()">↩️ Deshacer Último</button>
            <button onclick="copyToClipboard()">📋 Copiar Simple</button>
            <button onclick="copyAsPython()">🐍 Copiar como Python</button>
            <div class="info">
                <strong>Burbujas:</strong> <span id="bubbleCount">0</span>
            </div>
            <div style="display: flex; align-items: center; gap: 10px; background: #0f3460; padding: 10px 15px; border-radius: 5px;">
                <label style="color: #16c79a; font-weight: bold; white-space: nowrap;">📏 Tamaño Burbuja:</label>
                <input type="range" id="bubbleSize" min="20" max="60" value="35" 
                       oninput="updateBubbleSizeDisplay()" 
                       style="width: 150px;">
                <span id="bubbleSizeDisplay" style="color: #fff; font-weight: bold; min-width: 50px;">35px</span>
            </div>
        </div>
        
        <div class="controls-sticky">
            <label style="color: #16c79a; font-weight: bold;">📝 Tipo de Sección:</label>
            <select id="sectionType" onchange="changeSectionType()" style="padding: 10px; border-radius: 5px; background: #0f3460; color: #fff; border: 1px solid #16c79a; font-size: 1em;">
                                <option value="custom">Personalizado</option>
                <option value="folio">Folio (F1-F9, dígitos 0-9)</option>
                <option value="referencia-i">Referencia I (Preguntas 1-24, SI/NO)</option>
                <optgroup label="Likert 23">
                    <option value="likert-23">Likert 23 - Preguntas (1-23, A/B/C/D)</option>
                    <option value="likert-23-puestos">Likert 23 - Puestos (lista)</option>
                    <option value="likert-23-areas">Likert 23 - Áreas (lista)</option>
                    <option value="likert-genero">Likert 23 - Género (2 opciones)</option>
                    <option value="likert-turno">Likert 23 - Turno (3 opciones)</option>
                    <option value="likert-tipo-contrato">Likert 23 - Tipo de contrato (4 opciones)</option>
                </optgroup>
                <optgroup label="Referencia III - Secciones">
                    <option value="referencia-iii">Ref III - Generales (1-64, A/B/C/D/E)</option>
                    <option value="referencia-iii-cond-customer">Ref III - Condición Servicio Cliente (SÍ/NO)</option>
                    <option value="referencia-iii-customer">Ref III - Servicio Cliente (65-68, A/B/C/D/E)</option>
                    <option value="referencia-iii-cond-management">Ref III - Condición Gestión (SÍ/NO)</option>
                    <option value="referencia-iii-management">Ref III - Gestión/Supervisión (69-72, A/B/C/D/E)</option>
                    <option value="referencia-iii-citsats">Ref III - CITSATS (1-6, SÍ/NO)</option>
                </optgroup>
                <optgroup label="Referencia V - Datos Demográficos">
                    <option value="referencia-v-sexo">Ref V - Sexo/Género (2 opciones)</option>
                    <option value="referencia-v-edad">Ref V - Edad (2 dígitos 0-9)</option>
                    <option value="referencia-v-estado-civil">Ref V - Estado Civil (5 opciones)</option>
                    <option value="referencia-v-tipo-personal">Ref V - Tipo de Personal (3 opciones)</option>
                    <option value="referencia-v-estudios-sin-formacion">Ref V - Sin Formación (1 opción)</option>
                    <option value="referencia-v-estudios-primaria">Ref V - Primaria (Terminada/Incompleta)</option>
                    <option value="referencia-v-estudios-secundaria">Ref V - Secundaria (Terminada/Incompleta)</option>
                    <option value="referencia-v-estudios-preparatoria">Ref V - Preparatoria (Terminada/Incompleta)</option>
                    <option value="referencia-v-estudios-tecnico">Ref V - Técnico Superior (Terminada/Incompleta)</option>
                    <option value="referencia-v-estudios-licenciatura">Ref V - Licenciatura (Terminada/Incompleta)</option>
                    <option value="referencia-v-estudios-maestria">Ref V - Maestría (Terminada/Incompleta)</option>
                    <option value="referencia-v-estudios-doctorado">Ref V - Doctorado (Terminada/Incompleta)</option>
                    <option value="referencia-v-tipo-puesto">Ref V - Tipo de Puesto (4 opciones)</option>
                    <option value="referencia-v-tipo-contratacion">Ref V - Tipo de Contratación (4 opciones)</option>
                    <option value="referencia-v-tipo-jornada">Ref V - Tipo de Jornada (3 opciones)</option>
                    <option value="referencia-v-rotacion-turnos">Ref V - Rotación de Turnos (SÍ/NO)</option>
                    <option value="referencia-v-tiempo-puesto">Ref V - Tiempo en Puesto Actual (8 opciones)</option>
                    <option value="referencia-v-experiencia-laboral">Ref V - Experiencia Vida Laboral (6 opciones)</option>
                    <option value="referencia-v-ocupacion">Ref V - Ocupación/Profesión (2 filas x 5 columnas A-E)</option>
                    <option value="referencia-v-departamento">Ref V - Departamento/Sección (2 filas x 5 columnas A-E)</option>
                </optgroup>
            </select>
            <input type="number" id="startQuestion" value="1" min="1" placeholder="Pregunta inicial" style="width: 120px; padding: 10px; border-radius: 5px; background: #0f3460; color: #fff; border: 1px solid #16c79a; font-size: 1em;">
            <span style="color: #16c79a; font-weight: bold; font-size: 1.1em; background: #0f3460; padding: 10px 15px; border-radius: 5px; border: 2px solid #16c79a;">
                📍 <span id="currentItem" style="color: #fff;">-</span>
            </span>
        </div>

        <div class="canvas-container">
            <canvas id="canvas"></canvas>
        </div>

        <div class="coordinates-list">
            <h2>📍 Coordenadas Capturadas</h2>
            <div id="coordsList"></div>
        </div>
    </div>

    <div class="mouse-pos" id="mousePos">
        Mouse: (0, 0)
    </div>

    <script>
        const canvas = document.getElementById('canvas');
        const ctx = canvas.getContext('2d');
        let coordinates = [];
        let img = new Image();
        
        // Cargar imagen
        img.onload = function() {
            console.log('✅ Imagen cargada:', img.width, 'x', img.height);
            canvas.width = img.width;
            canvas.height = img.height;
            drawCanvas();
        };
        img.onerror = function(e) {
            console.error('❌ Error cargando imagen:', e);
            alert('Error: No se pudo cargar la imagen. Verifica la consola del navegador.');
        };
        img.src = 'data:image/png;base64,{{ image_data }}';
        console.log('🔄 Iniciando carga de imagen...');

        // Función para obtener el tamaño de burbuja actual
        function getBubbleSize() {
            return parseInt(document.getElementById('bubbleSize').value) || 35;
        }

        // Función para actualizar el display del tamaño
        function updateBubbleSizeDisplay() {
            const size = getBubbleSize();
            document.getElementById('bubbleSizeDisplay').textContent = size + 'px';
            drawCanvas(); // Redibujar para mostrar el preview del tamaño
        }

        // Manejar clics en el canvas - MODO CENTRO
        canvas.addEventListener('click', function(e) {
            const rect = canvas.getBoundingClientRect();
            const scaleX = canvas.width / rect.width;
            const scaleY = canvas.height / rect.height;
            const centerX = Math.round((e.clientX - rect.left) * scaleX);
            const centerY = Math.round((e.clientY - rect.top) * scaleY);
            
            const bubbleSize = getBubbleSize();
            
            // Calcular coordenadas del rectángulo desde el centro
            const x = centerX - Math.floor(bubbleSize / 2);
            const y = centerY - Math.floor(bubbleSize / 2);
            
            // Guardar como burbuja completa directamente
            coordinates.push({
                centerX: centerX,
                centerY: centerY,
                x: x,
                y: y,
                width: bubbleSize,
                height: bubbleSize
            });
            
            // Enviar al servidor
            fetch('/add_coordinate', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    centerX: centerX,
                    centerY: centerY,
                    x: x,
                    y: y,
                    width: bubbleSize,
                    height: bubbleSize
                })
            }).then(() => {
                updateDisplay();
                drawCanvas();
                updateCurrentItem();
            });
        });

        // Manejar movimiento del mouse - Mostrar preview de burbuja
        let mouseX = 0, mouseY = 0;
        canvas.addEventListener('mousemove', function(e) {
            const rect = canvas.getBoundingClientRect();
            const scaleX = canvas.width / rect.width;
            const scaleY = canvas.height / rect.height;
            mouseX = Math.round((e.clientX - rect.left) * scaleX);
            mouseY = Math.round((e.clientY - rect.top) * scaleY);
            
            document.getElementById('mousePos').textContent = `Mouse: (${mouseX}, ${mouseY})`;
            drawCanvas(); // Redibujar para mostrar el preview
        });

        function drawCanvas() {
            // Dibujar imagen base
            ctx.drawImage(img, 0, 0);
            
            // Dibujar burbujas capturadas
            coordinates.forEach((bubble, i) => {
                // Dibujar punto central
                ctx.fillStyle = '#16c79a';
                ctx.beginPath();
                ctx.arc(bubble.centerX, bubble.centerY, 4, 0, 2 * Math.PI);
                ctx.fill();
                
                // Dibujar rectángulo de la burbuja
                ctx.strokeStyle = '#16c79a';
                ctx.lineWidth = 2;
                ctx.strokeRect(bubble.x, bubble.y, bubble.width, bubble.height);
                
                // Dibujar número de burbuja
                ctx.fillStyle = '#fff';
                ctx.strokeStyle = '#000';
                ctx.lineWidth = 3;
                ctx.font = 'bold 16px Arial';
                ctx.strokeText(i + 1, bubble.centerX + 12, bubble.centerY - 12);
                ctx.fillText(i + 1, bubble.centerX + 12, bubble.centerY - 12);
            });
            
            // Dibujar preview de burbuja en posición del mouse
            if (mouseX > 0 && mouseY > 0) {
                const size = getBubbleSize();
                const previewX = mouseX - Math.floor(size / 2);
                const previewY = mouseY - Math.floor(size / 2);
                
                ctx.strokeStyle = 'rgba(255, 107, 107, 0.6)';
                ctx.lineWidth = 2;
                ctx.setLineDash([5, 5]);
                ctx.strokeRect(previewX, previewY, size, size);
                ctx.setLineDash([]);
                
                // Cruz en el centro
                ctx.strokeStyle = 'rgba(255, 107, 107, 0.8)';
                ctx.lineWidth = 1;
                ctx.beginPath();
                ctx.moveTo(mouseX - 10, mouseY);
                ctx.lineTo(mouseX + 10, mouseY);
                ctx.moveTo(mouseX, mouseY - 10);
                ctx.lineTo(mouseX, mouseY + 10);
                ctx.stroke();
            }
        }

        function updateDisplay() {
            document.getElementById('bubbleCount').textContent = coordinates.length;
            
            const section = document.getElementById('sectionType').value;
            const startQuestion = parseInt(document.getElementById('startQuestion').value) || 1;
            
            let html = '';
            coordinates.forEach((bubble, i) => {
                let label = getItemLabel(section, i, startQuestion);
                html += `<div class="coord-item bubble-complete">
                    <strong>${label}:</strong> (${bubble.x}, ${bubble.y}, ${bubble.width}, ${bubble.height})
                    <br><small style="color: #aaa;">Centro: (${bubble.centerX}, ${bubble.centerY})</small>
                </div>`;
            });
            
            document.getElementById('coordsList').innerHTML = html || '<p style="color: #888;">No hay coordenadas capturadas aún...</p>';
        }
        
        function getItemLabel(section, bubbleIndex, startQuestion) {
            if (section === 'folio') {
                const columnIndex = Math.floor(bubbleIndex / 10);
                const digitIndex = bubbleIndex % 10;
                return `F${columnIndex + 1}, Dígito ${digitIndex}`;
            } else if (section === 'referencia-i') {
                const questionIndex = Math.floor(bubbleIndex / 2);
                const optionIndex = bubbleIndex % 2;
                return `Pregunta ${startQuestion + questionIndex}, ${optionIndex === 0 ? 'SI' : 'NO'}`;
            } else if (section === 'likert-23') {
                const questionIndex = Math.floor(bubbleIndex / 4);
                const optionIndex = bubbleIndex % 4;
                const options = ['A', 'B', 'C', 'D'];
                return `Pregunta ${startQuestion + questionIndex}, ${options[optionIndex]}`;
            } else if (section === 'likert-23-puestos') {
                return `Puesto ${bubbleIndex + 1} de 24`;
            } else if (section === 'likert-23-areas') {
                return `Área ${bubbleIndex + 1} de 17`;
            } else if (section === 'likert-genero') {
                const options = ['Masculino', 'Femenino'];
                return options[bubbleIndex] || `Opción ${bubbleIndex + 1}`;
            } else if (section === 'likert-turno') {
                const options = ['Matutino', 'Vespertino', 'Nocturno'];
                return options[bubbleIndex] || `Opción ${bubbleIndex + 1}`;
            } else if (section === 'likert-tipo-contrato') {
                const options = ['Por obra o proyecto', 'Por tiempo determinado', 'Tiempo indeterminado', 'Honorarios'];
                return options[bubbleIndex] || `Opción ${bubbleIndex + 1}`;
            } else if (section === 'referencia-iii') {
                const questionIndex = Math.floor(bubbleIndex / 5);
                const optionIndex = bubbleIndex % 5;
                const options = ['A', 'B', 'C', 'D', 'E'];
                return `Pregunta ${startQuestion + questionIndex}, ${options[optionIndex]}`;
            } else if (section === 'referencia-iii-cond-customer') {
                return `Condición Servicio Cliente, ${bubbleIndex === 0 ? 'SÍ' : 'NO'}`;
            } else if (section === 'referencia-iii-cond-management') {
                return `Condición Gestión, ${bubbleIndex === 0 ? 'SÍ' : 'NO'}`;
            } else if (section === 'referencia-iii-customer') {
                const questionIndex = Math.floor(bubbleIndex / 5);
                const optionIndex = bubbleIndex % 5;
                const options = ['A', 'B', 'C', 'D', 'E'];
                return `Pregunta ${65 + questionIndex}, ${options[optionIndex]}`;
            } else if (section === 'referencia-iii-management') {
                const questionIndex = Math.floor(bubbleIndex / 5);
                const optionIndex = bubbleIndex % 5;
                const options = ['A', 'B', 'C', 'D', 'E'];
                return `Pregunta ${69 + questionIndex}, ${options[optionIndex]}`;
            } else if (section === 'referencia-iii-citsats') {
                const questionIndex = Math.floor(bubbleIndex / 2);
                const optionIndex = bubbleIndex % 2;
                return `CITSATS ${startQuestion + questionIndex}, ${optionIndex === 0 ? 'SI' : 'NO'}`;
            } else if (section === 'referencia-v-sexo') {
                const options = ['Masculino', 'Femenino'];
                return options[bubbleIndex] || `Opción ${bubbleIndex + 1}`;
            } else if (section === 'referencia-v-edad') {
                if (bubbleIndex < 10) {
                    return `Edad Decenas: ${bubbleIndex}`;
                } else {
                    return `Edad Unidades: ${bubbleIndex - 10}`;
                }
            } else if (section === 'referencia-v-estado-civil') {
                const options = ['Casado', 'Soltero', 'Unión libre', 'Divorciado', 'Viudo'];
                return options[bubbleIndex] || `Opción ${bubbleIndex + 1}`;
            } else if (section === 'referencia-v-tipo-personal') {
                const options = ['Sindicalizado', 'Confianza', 'Ninguno'];
                return options[bubbleIndex] || `Opción ${bubbleIndex + 1}`;
            } else if (section === 'referencia-v-estudios-sin-formacion') {
                return 'Sin formación';
            } else if (section === 'referencia-v-estudios-primaria') {
                const options = ['Primaria Terminada', 'Primaria Incompleta'];
                return options[bubbleIndex] || `Opción ${bubbleIndex + 1}`;
            } else if (section === 'referencia-v-estudios-secundaria') {
                const options = ['Secundaria Terminada', 'Secundaria Incompleta'];
                return options[bubbleIndex] || `Opción ${bubbleIndex + 1}`;
            } else if (section === 'referencia-v-estudios-preparatoria') {
                const options = ['Preparatoria Terminada', 'Preparatoria Incompleta'];
                return options[bubbleIndex] || `Opción ${bubbleIndex + 1}`;
            } else if (section === 'referencia-v-estudios-tecnico') {
                const options = ['Técnico Superior Terminada', 'Técnico Superior Incompleta'];
                return options[bubbleIndex] || `Opción ${bubbleIndex + 1}`;
            } else if (section === 'referencia-v-estudios-licenciatura') {
                const options = ['Licenciatura Terminada', 'Licenciatura Incompleta'];
                return options[bubbleIndex] || `Opción ${bubbleIndex + 1}`;
            } else if (section === 'referencia-v-estudios-maestria') {
                const options = ['Maestría Terminada', 'Maestría Incompleta'];
                return options[bubbleIndex] || `Opción ${bubbleIndex + 1}`;
            } else if (section === 'referencia-v-estudios-doctorado') {
                const options = ['Doctorado Terminado', 'Doctorado Incompleto'];
                return options[bubbleIndex] || `Opción ${bubbleIndex + 1}`;
            } else if (section === 'referencia-v-tipo-puesto') {
                const options = ['Operativo', 'Profesional o técnico', 'Supervisor', 'Gerente'];
                return options[bubbleIndex] || `Opción ${bubbleIndex + 1}`;
            } else if (section === 'referencia-v-tipo-contratacion') {
                const options = ['Por obra o proyecto', 'Por tiempo determinado', 'Tiempo indeterminado', 'Honorarios'];
                return options[bubbleIndex] || `Opción ${bubbleIndex + 1}`;
            } else if (section === 'referencia-v-tipo-jornada') {
                const options = ['Fijo nocturno', 'Fijo diurno', 'Fijo mixto'];
                return options[bubbleIndex] || `Opción ${bubbleIndex + 1}`;
            } else if (section === 'referencia-v-rotacion-turnos') {
                const options = ['Sí', 'No'];
                return `Rotación: ${options[bubbleIndex]}`;
            } else if (section === 'referencia-v-tiempo-puesto') {
                const options = ['< 6 meses', '6 meses-1 año', '1-4 años', '5-9 años', '10-14 años', '15-19 años', '20-24 años', '25+ años'];
                return options[bubbleIndex] || `Opción ${bubbleIndex + 1}`;
            } else if (section === 'referencia-v-experiencia-laboral') {
                const options = ['< 6 meses', '6 meses-1 año', '1-4 años', '5-9 años', '10-14 años', '15-19 años'];
                return options[bubbleIndex] || `Opción ${bubbleIndex + 1}`;
            } else if (section === 'referencia-v-ocupacion') {
                if (bubbleIndex < 5) {
                    return `Ocupación Fila 1, Col ${String.fromCharCode(65 + bubbleIndex)}`;
                } else {
                    return `Ocupación Fila 2, Col ${String.fromCharCode(65 + (bubbleIndex - 5))}`;
                }
            } else if (section === 'referencia-v-departamento') {
                if (bubbleIndex < 5) {
                    return `Departamento Fila 1, Col ${String.fromCharCode(65 + bubbleIndex)}`;
                } else {
                    return `Departamento Fila 2, Col ${String.fromCharCode(65 + (bubbleIndex - 5))}`;
                }
            } else {
                return `Burbuja ${bubbleIndex + 1}`;
            }
        }

        function resetCoordinates() {
            if (confirm('¿Estás seguro de que quieres reiniciar todas las coordenadas?')) {
                fetch('/reset', {method: 'POST'})
                    .then(() => {
                        coordinates = [];
                        updateDisplay();
                        drawCanvas();
                        updateCurrentItem();
                    });
            }
        }

        function undoLast() {
            if (coordinates.length > 0) {
                coordinates.pop();
                fetch('/undo', {method: 'POST'})
                    .then(() => {
                        updateDisplay();
                        drawCanvas();
                        updateCurrentItem();
                    });
            }
        }
        function copyToClipboard() {
            let text = 'Coordenadas de burbujas:\\n\\n';
            coordinates.forEach((bubble, i) => {
                text += `'${i}': (${bubble.x}, ${bubble.y}, ${bubble.width}, ${bubble.height}),\\n`;
            });
            
            navigator.clipboard.writeText(text).then(() => {
                alert('✅ Coordenadas copiadas al portapapeles!');
            });
        }

        function copyAsPython() {
            fetch('/generate_python')
                .then(response => response.json())
                .then(data => {
                    navigator.clipboard.writeText(data.code).then(() => {
                        alert('✅ Código Python copiado al portapapeles!\\n\\nPuedes pegarlo directamente en config_legacy.py');
                    });
                });
        }

        function changeSectionType() {
            const section = document.getElementById('sectionType').value;
            const startQuestion = parseInt(document.getElementById('startQuestion').value) || 1;
            
            fetch('/set_section', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({section, startQuestion})
            }).then(response => response.json())
                .then(data => {
                    updateCurrentItem();
                });
        }

        function updateCurrentItem() {
            const section = document.getElementById('sectionType').value;
            const bubbleCount = coordinates.length; // Ahora cada elemento ES una burbuja completa
            const startQuestion = parseInt(document.getElementById('startQuestion').value) || 1;
            let itemText = '-';
            
            if (section === 'folio') {
                const columnIndex = Math.floor(bubbleCount / 10);
                const digitIndex = bubbleCount % 10;
                if (columnIndex < 9) {
                    itemText = `F${columnIndex + 1}, Dígito ${digitIndex}`;
                }
            } else if (section === 'referencia-i') {
                const questionIndex = Math.floor(bubbleCount / 2);
                const optionIndex = bubbleCount % 2;
                itemText = `Pregunta ${startQuestion + questionIndex}, ${optionIndex === 0 ? 'SI' : 'NO'}`;
            } else if (section === 'likert-23') {
                const questionIndex = Math.floor(bubbleCount / 4);
                const optionIndex = bubbleCount % 4;
                const options = ['A', 'B', 'C', 'D'];
                itemText = `Pregunta ${startQuestion + questionIndex}, ${options[optionIndex]}`;
            } else if (section === 'likert-23-puestos') {
                itemText = `Puesto ${bubbleCount + 1} de 24`;
                if (bubbleCount >= 24) itemText = 'Completo (24 puestos)';
            } else if (section === 'likert-23-areas') {
                itemText = `Área ${bubbleCount + 1} de 17`;
                if (bubbleCount >= 17) itemText = 'Completo (17 áreas)';
            } else if (section === 'likert-genero') {
                const options = ['Masculino', 'Femenino'];
                itemText = bubbleCount < options.length ? options[bubbleCount] : 'Completo';
            } else if (section === 'likert-turno') {
                const options = ['Matutino', 'Vespertino', 'Nocturno'];
                itemText = bubbleCount < options.length ? options[bubbleCount] : 'Completo';
            } else if (section === 'likert-tipo-contrato') {
                const options = ['Por obra o proyecto', 'Por tiempo determinado', 'Tiempo indeterminado', 'Honorarios'];
                itemText = bubbleCount < options.length ? options[bubbleCount] : 'Completo';
            } else if (section === 'referencia-iii') {
                const questionIndex = Math.floor(bubbleCount / 5);
                const optionIndex = bubbleCount % 5;
                const options = ['A (Siempre)', 'B (Casi siempre)', 'C (Algunas veces)', 'D (Casi nunca)', 'E (Nunca)'];
                itemText = `Pregunta ${startQuestion + questionIndex}, ${options[optionIndex]}`;
            } else if (section === 'referencia-iii-cond-customer' || section === 'referencia-iii-cond-management') {
                const optionIndex = bubbleCount % 2;
                const condType = section === 'referencia-iii-cond-customer' ? 'Servicio Cliente' : 'Gestión';
                itemText = `Condición ${condType}, ${optionIndex === 0 ? 'SÍ' : 'NO'}`;
            } else if (section === 'referencia-iii-customer') {
                const questionIndex = Math.floor(bubbleCount / 5);
                const optionIndex = bubbleCount % 5;
                const options = ['A (Siempre)', 'B (Casi siempre)', 'C (Algunas veces)', 'D (Casi nunca)', 'E (Nunca)'];
                itemText = `Pregunta ${65 + questionIndex}, ${options[optionIndex]}`;
            } else if (section === 'referencia-iii-management') {
                const questionIndex = Math.floor(bubbleCount / 5);
                const optionIndex = bubbleCount % 5;
                const options = ['A (Siempre)', 'B (Casi siempre)', 'C (Algunas veces)', 'D (Casi nunca)', 'E (Nunca)'];
                itemText = `Pregunta ${69 + questionIndex}, ${options[optionIndex]}`;
            } else if (section === 'referencia-iii-citsats') {
                const questionIndex = Math.floor(bubbleCount / 2);
                const optionIndex = bubbleCount % 2;
                itemText = `CITSATS ${startQuestion + questionIndex}, ${optionIndex === 0 ? 'SI' : 'NO'}`;
            } else if (section === 'referencia-v-sexo') {
                const options = ['Masculino', 'Femenino'];
                itemText = bubbleCount < 2 ? options[bubbleCount] : 'Completo';
            } else if (section === 'referencia-v-edad') {
                if (bubbleCount < 10) {
                    itemText = `Edad - Decenas: ${bubbleCount}`;
                } else if (bubbleCount < 20) {
                    itemText = `Edad - Unidades: ${bubbleCount - 10}`;
                } else {
                    itemText = 'Edad completa';
                }
            } else if (section === 'referencia-v-estado-civil') {
                const options = ['Casado', 'Soltero', 'Unión libre', 'Divorciado', 'Viudo'];
                itemText = bubbleCount < 5 ? options[bubbleCount] : 'Completo';
            } else if (section === 'referencia-v-tipo-personal') {
                const options = ['Sindicalizado', 'Confianza', 'Ninguno'];
                itemText = bubbleCount < 3 ? options[bubbleCount] : 'Completo';
            } else if (section === 'referencia-v-estudios-sin-formacion') {
                itemText = bubbleCount === 0 ? 'Sin formación' : 'Completo';
            } else if (section === 'referencia-v-estudios-primaria') {
                const options = ['Primaria Terminada', 'Primaria Incompleta'];
                itemText = bubbleCount < 2 ? options[bubbleCount] : 'Completo';
            } else if (section === 'referencia-v-estudios-secundaria') {
                const options = ['Secundaria Terminada', 'Secundaria Incompleta'];
                itemText = bubbleCount < 2 ? options[bubbleCount] : 'Completo';
            } else if (section === 'referencia-v-estudios-preparatoria') {
                const options = ['Preparatoria Terminada', 'Preparatoria Incompleta'];
                itemText = bubbleCount < 2 ? options[bubbleCount] : 'Completo';
            } else if (section === 'referencia-v-estudios-tecnico') {
                const options = ['Técnico Superior Terminada', 'Técnico Superior Incompleta'];
                itemText = bubbleCount < 2 ? options[bubbleCount] : 'Completo';
            } else if (section === 'referencia-v-estudios-licenciatura') {
                const options = ['Licenciatura Terminada', 'Licenciatura Incompleta'];
                itemText = bubbleCount < 2 ? options[bubbleCount] : 'Completo';
            } else if (section === 'referencia-v-estudios-maestria') {
                const options = ['Maestría Terminada', 'Maestría Incompleta'];
                itemText = bubbleCount < 2 ? options[bubbleCount] : 'Completo';
            } else if (section === 'referencia-v-estudios-doctorado') {
                const options = ['Doctorado Terminado', 'Doctorado Incompleto'];
                itemText = bubbleCount < 2 ? options[bubbleCount] : 'Completo';
            } else if (section === 'referencia-v-tipo-puesto') {
                const options = ['Operativo', 'Profesional o técnico', 'Supervisor', 'Gerente'];
                itemText = bubbleCount < 4 ? options[bubbleCount] : 'Completo';
            } else if (section === 'referencia-v-tipo-contratacion') {
                const options = ['Por obra o proyecto', 'Por tiempo determinado', 'Tiempo indeterminado', 'Honorarios'];
                itemText = bubbleCount < 4 ? options[bubbleCount] : 'Completo';
            } else if (section === 'referencia-v-tipo-jornada') {
                const options = ['Fijo nocturno', 'Fijo diurno', 'Fijo mixto'];
                itemText = bubbleCount < 3 ? options[bubbleCount] : 'Completo';
            } else if (section === 'referencia-v-rotacion-turnos') {
                const options = ['Sí', 'No'];
                itemText = bubbleCount < 2 ? `Rotación: ${options[bubbleCount]}` : 'Completo';
            } else if (section === 'referencia-v-tiempo-puesto') {
                const options = ['< 6 meses', '6 meses-1 año', '1-4 años', '5-9 años', '10-14 años', '15-19 años', '20-24 años', '25+ años'];
                itemText = bubbleCount < 8 ? options[bubbleCount] : 'Completo';
            } else if (section === 'referencia-v-experiencia-laboral') {
                const options = ['< 6 meses', '6 meses-1 año', '1-4 años', '5-9 años', '10-14 años', '15-19 años'];
                itemText = bubbleCount < 6 ? options[bubbleCount] : 'Completo';
            } else if (section === 'referencia-v-ocupacion') {
                if (bubbleCount < 5) {
                    itemText = `Ocupación - Fila 1, Columna ${String.fromCharCode(65 + bubbleCount)}`;
                } else if (bubbleCount < 10) {
                    itemText = `Ocupación - Fila 2, Columna ${String.fromCharCode(65 + (bubbleCount - 5))}`;
                } else {
                    itemText = 'Ocupación completa';
                }
            } else if (section === 'referencia-v-departamento') {
                if (bubbleCount < 5) {
                    itemText = `Departamento - Fila 1, Columna ${String.fromCharCode(65 + bubbleCount)}`;
                } else if (bubbleCount < 10) {
                    itemText = `Departamento - Fila 2, Columna ${String.fromCharCode(65 + (bubbleCount - 5))}`;
                } else {
                    itemText = 'Departamento completo';
                }
            } else if (section === 'referencia-v') {
                itemText = `Elemento ${bubbleCount + 1}`;
            } else {
                itemText = `Burbuja ${bubbleCount + 1}`;
            }
            
            document.getElementById('currentItem').textContent = itemText;
        }

        // Actualizar display inicial
        updateDisplay();
        updateCurrentItem();
    </script>
</body>
</html>
"""

@app.route('/')
def index():
    """Render the calibration interface."""
    return render_template_string(HTML_TEMPLATE, image_data=image_data)

@app.route('/add_coordinate', methods=['POST'])
def add_coordinate():
    """Add a bubble coordinate (center-based mode)."""
    data = request.json
    
    # Agregar la burbuja completa con toda su información
    bubble = {
        'centerX': data['centerX'],
        'centerY': data['centerY'],
        'x': data['x'],
        'y': data['y'],
        'width': data['width'],
        'height': data['height']
    }
    coordinates.append(bubble)
    
    print(f"Burbuja {len(coordinates)}: Centro({bubble['centerX']}, {bubble['centerY']}) → Rect({bubble['x']}, {bubble['y']}, {bubble['width']}, {bubble['height']})")
    
    return jsonify({'success': True})

@app.route('/reset', methods=['POST'])
def reset():
    """Reset all coordinates."""
    global coordinates
    coordinates = []
    print("\n--- Coordenadas reiniciadas ---\n")
    return jsonify({'success': True})

@app.route('/undo', methods=['POST'])
def undo():
    """Undo last coordinate."""
    if coordinates:
        coordinates.pop()
    return jsonify({'success': True})

@app.route('/set_section', methods=['POST'])
def set_section():
    """Set the current calibration section."""
    global current_section, current_question
    data = request.json
    current_section = data.get('section', 'custom')
    current_question = data.get('startQuestion', 1)
    print(f"\n📝 Cambiado a sección: {current_section}, pregunta inicial: {current_question}\n")
    return jsonify({'success': True})

@app.route('/generate_python')
def generate_python():
    """Generate Python code for config.py based on current section."""
    try:
        code = generate_config_code()
        return jsonify({'code': code})
    except Exception as e:
        import traceback
        traceback.print_exc()
        return jsonify({'error': str(e)}), 500

def generate_config_code():
    """Generate the appropriate Python code based on section type."""
    if len(coordinates) < 1:
        return "# No hay coordenadas para generar código"
    
    # Ahora coordinates ya contiene burbujas completas
    bubbles = [(b['x'], b['y'], b['width'], b['height']) for b in coordinates]
    
    if current_section == 'folio':
        return generate_folio_code(bubbles)
    elif current_section == 'referencia-i':
        return generate_referencia_i_code(bubbles)
    elif current_section == 'referencia-iii':
        return generate_referencia_iii_code(bubbles)
    elif current_section == 'referencia-iii-cond-customer':
        return generate_conditional_code(bubbles, 'customer_service', 'Servicio al Cliente')
    elif current_section == 'referencia-iii-customer':
        return generate_referencia_iii_customer_code(bubbles)
    elif current_section == 'referencia-iii-cond-management':
        return generate_conditional_code(bubbles, 'management', 'Gestión/Supervisión')
    elif current_section == 'referencia-iii-management':
        return generate_referencia_iii_management_code(bubbles)
    elif current_section == 'referencia-iii-citsats':
        return generate_citsats_code(bubbles)
    elif current_section == 'referencia-v':
        return generate_referencia_v_code(bubbles)
    # Nuevas secciones específicas de Referencia V
    elif current_section == 'referencia-v-sexo':
        return generate_referencia_v_simple_code(bubbles, 'sexo', ['Masculino', 'Femenino'])
    elif current_section == 'referencia-v-edad':
        return generate_referencia_v_edad_code(bubbles)
    elif current_section == 'referencia-v-estado-civil':
        return generate_referencia_v_simple_code(bubbles, 'estado_civil', ['Casado', 'Soltero', 'Unión libre', 'Divorciado', 'Viudo'])
    elif current_section == 'referencia-v-tipo-personal':
        return generate_referencia_v_simple_code(bubbles, 'tipo_personal', ['Sindicalizado', 'Confianza', 'Ninguno'])
    elif current_section == 'referencia-v-estudios-sin-formacion':
        return generate_referencia_v_simple_code(bubbles, 'sin_formacion', ['Sin formación'])
    elif current_section == 'referencia-v-estudios-primaria':
        return generate_referencia_v_estudios_code(bubbles, 'primaria', 'Primaria')
    elif current_section == 'referencia-v-estudios-secundaria':
        return generate_referencia_v_estudios_code(bubbles, 'secundaria', 'Secundaria')
    elif current_section == 'referencia-v-estudios-preparatoria':
        return generate_referencia_v_estudios_code(bubbles, 'preparatoria', 'Preparatoria o Bachillerato')
    elif current_section == 'referencia-v-estudios-tecnico':
        return generate_referencia_v_estudios_code(bubbles, 'tecnico_superior', 'Técnico Superior')
    elif current_section == 'referencia-v-estudios-licenciatura':
        return generate_referencia_v_estudios_code(bubbles, 'licenciatura', 'Licenciatura')
    elif current_section == 'referencia-v-estudios-maestria':
        return generate_referencia_v_estudios_code(bubbles, 'maestria', 'Maestría')
    elif current_section == 'referencia-v-estudios-doctorado':
        return generate_referencia_v_estudios_code(bubbles, 'doctorado', 'Doctorado')
    elif current_section == 'referencia-v-tipo-puesto':
        return generate_referencia_v_simple_code(bubbles, 'tipo_puesto', ['Operativo', 'Profesional o técnico', 'Supervisor', 'Gerente'])
    elif current_section == 'referencia-v-tipo-contratacion':
        return generate_referencia_v_simple_code(bubbles, 'tipo_contratacion', ['Por obra o proyecto', 'Por tiempo determinado (temporal)', 'Tiempo indeterminado', 'Honorarios'])
    elif current_section == 'referencia-v-tipo-jornada':
        return generate_referencia_v_simple_code(bubbles, 'tipo_jornada', ['Fijo nocturno (entre las 20:00 y 6:00 hrs)', 'Fijo diurno (entre las 6:00 y 20:00 hrs)', 'Fijo mixto (combinación de nocturno y diurno)'])
    elif current_section == 'referencia-v-rotacion-turnos':
        return generate_referencia_v_simple_code(bubbles, 'rotacion_turnos', ['Sí', 'No'])
    elif current_section == 'referencia-v-tiempo-puesto':
        return generate_referencia_v_simple_code(bubbles, 'tiempo_puesto_actual', ['Menos de 6 meses', 'Entre 6 meses y 1 año', 'Entre 1 a 4 años', 'Entre 5 a 9 años', 'Entre 10 a 14 años', 'Entre 15 a 19 años', 'Entre 20 a 24 años', '25 años o más'])
    elif current_section == 'referencia-v-experiencia-laboral':
        return generate_referencia_v_simple_code(bubbles, 'experiencia_laboral', ['Menos de 6 meses', 'Entre 6 meses y 1 año', 'Entre 1 a 4 años', 'Entre 5 a 9 años', 'Entre 10 a 14 años', 'Entre 15 a 19 años'])
    elif current_section == 'referencia-v-ocupacion':
        return generate_referencia_v_coding_code(bubbles, 'ocupacion', 'Ocupación/Profesión/Puesto')
    elif current_section == 'referencia-v-departamento':
        return generate_referencia_v_coding_code(bubbles, 'departamento', 'Departamento/Sección/Área')
    elif current_section == 'likert-23':
        return generate_likert_23_code(bubbles)
    elif current_section == 'likert-23-puestos':
        return generate_single_list_first_code(bubbles, 'likert_puestos', 'Likert Puestos', total_items=24)
    elif current_section == 'likert-23-areas':
        return generate_single_list_first_code(bubbles, 'likert_areas', 'Likert Áreas', total_items=17)
    elif current_section == 'likert-genero':
        return generate_referencia_v_simple_code(bubbles, 'likert_genero', ['Masculino', 'Femenino'])
    elif current_section == 'likert-turno':
        return generate_referencia_v_simple_code(bubbles, 'likert_turno', ['Matutino', 'Vespertino', 'Nocturno'])
    elif current_section == 'likert-tipo-contrato':
        return generate_referencia_v_simple_code(bubbles, 'likert_tipo_contrato', ['Por obra o proyecto', 'Por tiempo determinado', 'Tiempo indeterminado', 'Honorarios'])
    else:
        return generate_custom_code(bubbles)

def generate_folio_code(bubbles):
    """Generate code for folio configuration (9 columns x 10 digits)."""
    code = "folio_configuration = {\n"
    
    column_names = ['F1', 'F2', 'F3', 'F4', 'F5', 'F6', 'F7', 'F8', 'F9']
    bubbles_per_column = 10
    
    for col_idx in range(9):
        if col_idx * bubbles_per_column >= len(bubbles):
            break
            
        code += f"    '{column_names[col_idx]}': {{\n"
        
        for digit in range(10):
            bubble_idx = col_idx * bubbles_per_column + digit
            if bubble_idx < len(bubbles):
                x, y, w, h = bubbles[bubble_idx]
                code += f"        '{digit}': ({x}, {y}, {w}, {h}),\n"
        
        code += "    },\n"
    
    code += "}\n"
    return code

def generate_referencia_i_code(bubbles):
    """Generate code for Referencia I (24 questions, SI/NO)."""
    code = "reference_i = {\n"
    
    for q_idx in range(24):
        question_num = current_question + q_idx
        bubble_idx = q_idx * 2
        
        if bubble_idx + 1 >= len(bubbles):
            break
        
        code += f"    '{question_num}': {{\n"
        
        if bubble_idx < len(bubbles):
            x, y, w, h = bubbles[bubble_idx]
            code += f"        'SI': ({x}, {y}, {w}, {h}),\n"
        
        if bubble_idx + 1 < len(bubbles):
            x, y, w, h = bubbles[bubble_idx + 1]
            code += f"        'NO': ({x}, {y}, {w}, {h}),\n"
        
        code += "    },\n"
    
    code += "}\n"
    return code

def generate_referencia_iii_code(bubbles):
    """Generate code for Referencia III (46 questions, A/B/C/D/E)."""
    code = "referencia_iii = {\n"
    options = ['A', 'B', 'C', 'D', 'E']
    
    for q_idx in range(46):
        question_num = current_question + q_idx
        bubble_idx = q_idx * 5
        
        if bubble_idx >= len(bubbles):
            break
        
        code += f"    '{question_num}': {{\n"
        
        for opt_idx, option in enumerate(options):
            idx = bubble_idx + opt_idx
            if idx < len(bubbles):
                x, y, w, h = bubbles[idx]
                code += f"        '{option}': ({x}, {y}, {w}, {h}),\n"
        
        code += "    },\n"
    
    code += "}\n"
    return code

def generate_citsats_code(bubbles):
    """Generate code for CITSATS section (6 questions, SI/NO)."""
    code = "# Sección CITSATS-s1 (agregar a referencia_iii)\n"
    code += "citsats_s1 = {\n"
    
    for q_idx in range(6):
        question_num = current_question + q_idx
        bubble_idx = q_idx * 2
        
        if bubble_idx + 1 >= len(bubbles):
            break
        
        code += f"    '{question_num}': {{\n"
        
        if bubble_idx < len(bubbles):
            x, y, w, h = bubbles[bubble_idx]
            code += f"        'SI': ({x}, {y}, {w}, {h}),\n"
        
        if bubble_idx + 1 < len(bubbles):
            x, y, w, h = bubbles[bubble_idx + 1]
            code += f"        'NO': ({x}, {y}, {w}, {h}),\n"
        
        code += "    },\n"
    
    code += "}\n"
    return code

def generate_referencia_v_code(bubbles):
    """Generate code for Referencia V (demographic data)."""
    code = "# Referencia V - Datos Demográficos\n"
    code += "# NOTA: Ajusta los nombres de las claves según tu estructura\n\n"
    code += "reference_v = {\n"
    
    for idx, (x, y, w, h) in enumerate(bubbles):
        code += f"    'opcion_{idx}': ({x}, {y}, {w}, {h}),\n"
    
    code += "}\n"
    return code

def generate_conditional_code(bubbles, condition_key, condition_name):
    """Generate code for conditional questions (SÍ/NO)."""
    code = f"# Pregunta condicional: {condition_name}\n"
    code += f"conditional_{condition_key} = {{\n"
    code += f"    'condition': {{\n"
    
    if len(bubbles) >= 1:
        x, y, w, h = bubbles[0]
        code += f"        'SI': ({x}, {y}, {w}, {h}),\n"
    
    if len(bubbles) >= 2:
        x, y, w, h = bubbles[1]
        code += f"        'NO': ({x}, {y}, {w}, {h}),\n"
    
    code += "    },\n"
    code += "}\n"
    return code

def generate_referencia_iii_customer_code(bubbles):
    """Generate code for customer service questions 65-68 (A-E)."""
    code = "# Preguntas de Servicio al Cliente (65-68)\n"
    code += "customer_service_questions = {\n"
    
    options = ['A', 'B', 'C', 'D', 'E']
    
    for q_idx in range(4):  # 4 preguntas (65-68)
        question_num = 65 + q_idx
        bubble_idx = q_idx * 5
        
        if bubble_idx >= len(bubbles):
            break
        
        code += f"    '{question_num}': {{\n"
        
        for opt_idx, option in enumerate(options):
            idx = bubble_idx + opt_idx
            if idx < len(bubbles):
                x, y, w, h = bubbles[idx]
                code += f"        '{option}': ({x}, {y}, {w}, {h}),\n"
        
        code += "    },\n"
    
    code += "}\n"
    return code

def generate_referencia_iii_management_code(bubbles):
    """Generate code for management questions 69-72 (A-E)."""
    code = "# Preguntas de Gestión/Supervisión (69-72)\n"
    code += "management_questions = {\n"
    
    options = ['A', 'B', 'C', 'D', 'E']
    
    for q_idx in range(4):  # 4 preguntas (69-72)
        question_num = 69 + q_idx
        bubble_idx = q_idx * 5
        
        if bubble_idx >= len(bubbles):
            break
        
        code += f"    '{question_num}': {{\n"
        
        for opt_idx, option in enumerate(options):
            idx = bubble_idx + opt_idx
            if idx < len(bubbles):
                x, y, w, h = bubbles[idx]
                code += f"        '{option}': ({x}, {y}, {w}, {h}),\n"
        
        code += "    },\n"
    
    code += "}\n"
    return code

def generate_likert_23_code(bubbles):
    """Generate code for Likert evaluation (23 questions, A/B/C/D)."""
    code = "likert = {\n"
    options = ['A', 'B', 'C', 'D']
    for q_idx in range(23):
        question_num = current_question + q_idx
        bubble_idx = q_idx * 4
        if bubble_idx >= len(bubbles):
            break
        code += f"    '{question_num}': {{\n"
        for opt_idx, option in enumerate(options):
            idx = bubble_idx + opt_idx
            if idx < len(bubbles):
                x, y, w, h = bubbles[idx]
                code += f"        '{option}': ({x}, {y}, {w}, {h}),\n"
        code += "    },\n"
    code += "}\n"
    return code

def generate_single_list_first_code(bubbles, section_key, section_name, total_items='N'):
    """Generate code that captures only the first item's bubble for a vertical list (then iterate downstream)."""
    if not bubbles:
        return f"# {section_name}: no hay coordenadas capturadas\n{section_key} = {{}}\n"
    x, y, w, h = bubbles[0]

    code = f"# {section_name} (lista vertical de {total_items} items)\n"
    code += f"# Nota: Solo se tomó la primera posición. Itera hacia abajo en tu pipeline para capturar los {total_items} items.\n"
    code += f"{section_key} = {{\n"
    code += f"    'first': ({x}, {y}, {w}, {h}),\n"
    code += f"}}\n"
    return code

def generate_custom_code(bubbles):
    """Generate generic code for custom sections."""
    code = "# Coordenadas personalizadas\n"
    code += "custom_bubbles = {\n"
    
    for idx, (x, y, w, h) in enumerate(bubbles):
        code += f"    '{idx}': ({x}, {y}, {w}, {h}),\n"
    
    code += "}\n"
    return code

def generate_referencia_v_simple_code(bubbles, section_key, options):
    """Genera código para secciones simples de Referencia V con opciones predefinidas en formato variable = { clave: (x,y,w,h) }"""
    def normalize(label: str) -> str:
        nf = unicodedata.normalize('NFD', label)
        no_accents = ''.join(ch for ch in nf if unicodedata.category(ch) != 'Mn')
        return no_accents.lower().replace(' ', '_').replace('/', '_').replace('-', '_')

    code = f"{section_key} = {{\n"
    for i, b in enumerate(bubbles):
        x, y, w, h = b
        raw_label = options[i] if i < len(options) else f"Opción {i+1}"
        key = normalize(raw_label)
        code += f"    '{key}': ({x}, {y}, {w}, {h}),\n"
    code += "}\n"
    return code

def generate_referencia_v_edad_code(bubbles):
    """Genera código para la sección de edad con formato edad = { 'decenas': {'0':(...),...}, 'unidades': {...} }"""
    code = "edad = {\n"
    code += "    'decenas': {\n"
    for i in range(min(10, len(bubbles))):
        x, y, w, h = bubbles[i]
        code += f"        '{i}': ({x}, {y}, {w}, {h}),\n"
    code += "    },\n"
    code += "    'unidades': {\n"
    for i in range(10, min(20, len(bubbles))):
        digit = i - 10
        x, y, w, h = bubbles[i]
        code += f"        '{digit}': ({x}, {y}, {w}, {h}),\n"
    code += "    }\n"
    code += "}\n"
    return code

def generate_referencia_v_estudios_code(bubbles, section_key, nivel_name):
    """Genera código para secciones de estudios -> variable = { 'terminada': (...), 'incompleta': (...) }"""
    terminada_incompleta = ['Terminada', 'Incompleta']
    code = f"{section_key} = {{\n"
    for i, b in enumerate(bubbles):
        x, y, w, h = b
        raw_label = terminada_incompleta[i] if i < len(terminada_incompleta) else f"Opción {i+1}"
        key = 'terminada' if i == 0 else ('incompleta' if i == 1 else f'opcion_{i+1}')
        code += f"    '{key}': ({x}, {y}, {w}, {h}),  # {nivel_name} {raw_label}\n"
    code += "}\n"
    return code

def generate_referencia_v_coding_code(bubbles, section_key, section_name):
    """Genera código para grillas de codificación (2 filas x 5 columnas A-E) en formato variable = { 'fila1': {'A':(...)} }"""
    code = f"{section_key} = {{\n"
    code += "    'fila1': {\n"
    for i in range(min(5, len(bubbles))):
        x, y, w, h = bubbles[i]
        letter = chr(ord('A') + i)
        code += f"        '{letter}': ({x}, {y}, {w}, {h}),\n"
    code += "    },\n"
    code += "    'fila2': {\n"
    for i in range(5, min(10, len(bubbles))):
        x, y, w, h = bubbles[i]
        letter = chr(ord('A') + (i - 5))
        code += f"        '{letter}': ({x}, {y}, {w}, {h}),\n"
    code += "    }\n"
    code += "}\n"
    return code

def main():
    global image_path, image_data, image_width, image_height
    
    if len(sys.argv) < 2:
        print("Uso: python calibrate_bubbles.py <imagen_alineada.png> [puerto]")
        print("  Puerto por defecto: 5000")
        print("\nEjemplo:")
        print("  python calibrate_bubbles.py /app/outputs_aligned/page_1_aligned.png")
        print("\nLuego abre en tu navegador: http://localhost:5000")
        sys.exit(1)
    
    image_path = sys.argv[1]
    port = int(sys.argv[2]) if len(sys.argv) > 2 else 5000
    
    # Verificar que la imagen existe
    if not os.path.exists(image_path):
        print(f"❌ Error: No se encontró la imagen '{image_path}'")
        sys.exit(1)
    
    print(f"✅ Imagen encontrada: {image_path}")
    
    # Cargar y codificar imagen
    try:
        img = cv2.imread(image_path)
        if img is None:
            print(f"❌ Error: cv2.imread() devolvió None para '{image_path}'")
            sys.exit(1)
        
        print(f"✅ Imagen cargada con OpenCV")
        
        image_height, image_width = img.shape[:2]
        print(f"✅ Dimensiones detectadas: {image_width}x{image_height}")
        
        # Convertir a base64 para enviar al navegador
        success, buffer = cv2.imencode('.png', img)
        if not success:
            print(f"❌ Error: No se pudo codificar la imagen a PNG")
            sys.exit(1)
        
        print(f"✅ Imagen codificada a PNG")
        
        image_data = base64.b64encode(buffer).decode('utf-8')
        print(f"✅ Imagen convertida a base64 ({len(image_data)} caracteres)")
        
    except Exception as e:
        print(f"❌ Error al procesar la imagen: {e}")
        import traceback
        traceback.print_exc()
        sys.exit(1)
    
    print(f"\n{'='*60}")
    print(f"🚀 Servidor de Calibración OMR Iniciado")
    print(f"{'='*60}")
    print(f"📁 Imagen: {image_path}")
    print(f"📐 Dimensiones: {image_width}x{image_height}")
    print(f"🌐 URL: http://localhost:{port}")
    print(f"{'='*60}\n")
    print("📌 Abre la URL en tu navegador para comenzar la calibración")
    print("🛑 Presiona Ctrl+C para detener el servidor\n")
    
    # Ejecutar servidor Flask
    try:
        app.run(host='0.0.0.0', port=port, debug=True, use_reloader=False)
    except Exception as e:
        print(f"❌ Error al iniciar el servidor Flask: {e}")
        import traceback
        traceback.print_exc()
        sys.exit(1)

if __name__ == "__main__":
    main()
