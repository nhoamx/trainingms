# Usar una imagen de Python ligera
FROM python:3.13.1-slim

# Establecer el directorio de trabajo dentro del contenedor
WORKDIR /app

# Copiar los scripts Python al contenedor
COPY python-scripts /app/scripts

# Instalar las dependencias de Python
RUN pip install --no-cache-dir -r /app/scripts/requirements.txt

# Comando por defecto (se puede sobreescribir al ejecutar el contenedor)
CMD ["python", "/app/scripts/test.py"]
