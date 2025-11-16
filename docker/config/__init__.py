# Import from legacy config file for backward compatibility
import sys
import os

# Add parent directory to path to import config_legacy.py
parent_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
config_legacy_path = os.path.join(parent_dir, 'config_legacy.py')

# Import config_legacy module (contains all bubble coordinates and configurations)
if os.path.exists(config_legacy_path):
    sys.path.insert(0, parent_dir)
    try:
        import config_legacy
        
        # Expose all config_legacy attributes directly at module level
        # This allows: config.customer_service_questions instead of config.config_legacy.customer_service_questions
        folio_configuration = config_legacy.folio_configuration
        referencia_iii = config_legacy.referencia_iii
        conditional_customer_service = config_legacy.conditional_customer_service
        customer_service_questions = config_legacy.customer_service_questions
        conditional_management = config_legacy.conditional_management
        management_questions = config_legacy.management_questions
        citsats_s1 = config_legacy.citsats_s1
        
        # Import referencia_i and referencia_v (use legacy versions if they exist)
        if hasattr(config_legacy, 'referencia_i'):
            referencia_i = config_legacy.referencia_i
        if hasattr(config_legacy, 'referencia_v'):
            referencia_v = config_legacy.referencia_v
        
        # Import Referencia V demographic sections
        demographic_sections = ['sexo', 'edad', 'estado_civil', 'nivel_estudios', 'tiempo_puesto_actual',
                               'tipo_personal', 'tipo_puesto', 'tipo_contratacion', 'tipo_jornada',
                               'rotacion_turnos', 'experiencia_laboral', 'ocupacion', 'departamento',
                               'numero_trabajadores', 'subordinados', 'horas_laborales', 'salario']
        
        for section in demographic_sections:
            if hasattr(config_legacy, section):
                globals()[section] = getattr(config_legacy, section)
        
        # Import Likert (template 05) if present in legacy
        if hasattr(config_legacy, 'likert'):
            likert = config_legacy.likert
        
        # Keep reference to config_legacy for backward compatibility
        # This allows: config.config_legacy.variable_name (old style)
        # AND: config.variable_name (new clean style)
        
    except (ImportError, AttributeError) as e:
        print(f"Warning: Could not fully import config_legacy: {e}")
        # Fallback to local modules if config_legacy is incomplete
        from .folio import folio_configuration
        from .referencia_i import referencia_i
        from .referencia_iii import referencia_iii
        from .referencia_v import reference_v
        # Try to import Likert config if available
        try:
            from .likert import likert  # type: ignore
        except Exception:
            pass
    finally:
        # Clean up path
        if parent_dir in sys.path:
            sys.path.remove(parent_dir)

__all__ = [
    'folio_configuration',
    'referencia_iii',
    'referencia_i',
    'reference_v',
    'conditional_customer_service',
    'customer_service_questions',
    'conditional_management',
    'management_questions',
    'citsats_s1',
    'likert',
    # Referencia V demographic sections
    'sexo',
    'edad',
    'estado_civil',
    'nivel_estudios',
    'tiempo_puesto_actual',
    'tipo_personal',
    'tipo_puesto',
    'tipo_contratacion',
    'tipo_jornada',
    'rotacion_turnos',
    'experiencia_laboral',
    'ocupacion',
    'departamento',
    'numero_trabajadores',
    'subordinados',
    'horas_laborales',
    'salario',
    'config_legacy',
]
