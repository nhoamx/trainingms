# Config module for OMR templates

from .folio import folio_configuration
from .referencia_i import reference_i
from .referencia_iii import referencia_iii
from .referencia_v import referencia_v

# Import from legacy config file for backward compatibility
import sys
import os

# Add parent directory to path to import config_legacy.py
parent_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
config_legacy_path = os.path.join(parent_dir, 'config_legacy.py')

if os.path.exists(config_legacy_path):
    sys.path.insert(0, parent_dir)
    try:
        import config_legacy
        # Import evaluation configs from legacy config
        if hasattr(config_legacy, 'evaluation_01'):
            evaluation_01 = config_legacy.evaluation_01
        if hasattr(config_legacy, 'escala_cisneros'):
            escala_cisneros = config_legacy.escala_cisneros
        if hasattr(config_legacy, 'folio_configuration'):
            # Use legacy folio_configuration if it exists (it's more complete)
            folio_configuration = config_legacy.folio_configuration
        if hasattr(config_legacy, 'reference_i'):
            # Use legacy reference_i if it exists (it's more complete)
            reference_i = config_legacy.reference_i
        if hasattr(config_legacy, 'reference_v'):
            # Use legacy reference_v if it exists (it's more complete)
            reference_v = config_legacy.reference_v
    except ImportError as e:
        print(f"Warning: Could not import config_legacy: {e}")
    finally:
        # Clean up path
        if parent_dir in sys.path:
            sys.path.remove(parent_dir)

__all__ = [
    'folio_configuration',
    'reference_i',
    'referencia_iii',
    'reference_v',
    'evaluation_01',
    'escala_cisneros',
]
