export interface Organization {
    id: string;
    name: string;
}

export interface Evaluation {
    id: string;
    folio: string;
    created_at: string;
    personal_folio: string;
    has_guide_i: boolean;
    has_guide_iii: boolean;
    has_guide_v: boolean;
    has_cisneros?: boolean;
}

export interface CategoryScore {
    nombre: string;
    puntaje: number;
    nivel_riesgo?: string;
}

export interface DomainScore {
    nombre: string;
    puntaje: number;
    nivel_riesgo?: string;
}

export interface DetailedResultRow {
    categoria: CategoryScore;
    dominio: DomainScore;
    dimension: string;
    item: string;
    item_numero: number;
    respuesta: string;
    puntaje: number;
}

export interface GuideIResults {
    id: string;
    folio: string;
    created_at: string;
    answers: Record<string, string>;
}

export interface ConditionalSection {
    section: string;
    condition: string;
    questions: Record<string, string>;
}

export interface GuideIIIResults {
    id: string;
    folio: string;
    created_at: string;
    answers: Record<string, string>;
    conditional: ConditionalSection[];
    citsats_s1: Record<string, string>;
}

export interface GuideVResults {
    id: string;
    folio: string;
    created_at: string;
    demographic_data: Record<string, string>;
}

export interface CisnerosResults {
    id: string;
    folio: string;
    created_at: string;
    answers: Record<string, string>;
}

export interface Tab {
    key: string;
    label: string;
}

export interface GroupedCategory {
    nombre: string;
    puntaje: number;
    nivel_riesgo?: string;
    dominios: GroupedDomain[];
    rowspan: number;
}

export interface GroupedDomain {
    nombre: string;
    puntaje: number;
    nivel_riesgo?: string;
    dimensiones: GroupedDimension[];
    rowspan: number;
}

export interface GroupedDimension {
    nombre: string;
    items: GroupedItem[];
    rowspan: number;
}

export interface GroupedItem {
    nombre: string;
    puntaje: number;
}

export interface CategorySummary {
    name: string;
    score: number;
}

export interface DomainSummary {
    name: string;
    score: number;
}
