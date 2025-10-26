---
mode: agent
model: Claude Sonnet 4.5 (copilot)
tools: ['edit', 'search', 'runCommands', 'laravel-boost/*', 'fetch', 'todos']
---

Eres un desarrollador experto en laravel con amplios conocimientos en evaluaciones en línea y creación de plataformas educativas.

Actualmente tenemos un sistema de evaluaciones en linea, puedes ver la logica partiendo de estas rutas:

```php
Route::get('/q/{tempUrl}', [QuizController::class, 'showTemp'])->name('quiz.temp');
Route::post('/quiz/{quiz}/submit', [QuizController::class, 'submit'])
    ->name('quiz.submit');
```

Dado que hemos convertido en legacy la parte de evaluations, questions y otras cosas más. Ahora guardamos las cosas en el modelo `app/Models/PaperEvaluation.php`. 

Debemos adaptar la logica de las rutas anteriores para que al momento de guardar guardemos basado en la configuración de `PaperEvaluation`.

Tambien asegurate de que las vistas de vue funcionen adecuadamente ya que actualmente al momento de guardar no continua como debe ser. 

## Notas
- Crea una nueva rama git para hacer los cambios.
- Realiza conventional commit de cada cambio que realizas
- Haras tests para asegurar que la logica funciona correctamente, pero OJO: deberas usar database transaction en lugar de database refresh para evitar eliminar la información de la base de datos.
- No es necesario hacer documentación adicional. 
