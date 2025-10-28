/**
 * TypeScript types for Paper Evaluation Reports
 * Used by ReportSummaryDashboard and related components
 */

export interface RiskLevels {
    'Nulo': number;
    'Bajo': number;
    'Medio': number;
    'Alto': number;
    'Muy Alto': number;
}

export interface PersonalByRisk {
    'Nulo': string[];
    'Bajo': string[];
    'Medio': string[];
    'Alto': string[];
    'Muy Alto': string[];
}

export interface GroupedReportItem {
    name: string;
    risk_levels: RiskLevels;
    personal_by_risk: PersonalByRisk;
    total: number;
}

export interface CategoryReportItem {
    categoria: string;
    nivel_riesgo: string;
    conteo: number;
    personal: string[];
}

export interface DomainReportItem {
    dominio: string;
    nivel_riesgo: string;
    conteo: number;
    personal: string[];
}

export interface DimensionReportItem {
    dimension: string;
    nivel_riesgo: string;
    conteo: number;
    personal: string[];
}

export interface FinalRiskLevel {
    nivel_riesgo: string;
    conteo: number;
    personal: string[];
}

export interface ParticipantScore {
    personal_folio: string;
    folio: string;
    calificacion: number;
    nivel_riesgo: string;
    created_at: string;
}

export interface DetailedResultItem {
    nombre: string;
    puntaje: number;
    promedio?: number; // Precise average (e.g., 2.38)
    item_numero?: number | string;
}

export interface DetailedResultDimension {
    nombre: string;
    items: DetailedResultItem[];
    rowspan: number;
    sumatoria?: number; // Sum of rounded item averages
}

export interface DetailedResultDomain {
    nombre: string;
    dimensiones: DetailedResultDimension[];
    rowspan: number;
    sumatoria?: number; // Sum of dimension sumatorias
    nivel_riesgo?: string; // NOM-035 risk level: Nulo, Bajo, Medio, Alto, Muy Alto
}

export interface DetailedResultCategory {
    nombre: string;
    dominios: DetailedResultDomain[];
    rowspan: number;
    sumatoria?: number; // Sum of domain sumatorias
    nivel_riesgo?: string; // NOM-035 risk level: Nulo, Bajo, Medio, Alto, Muy Alto
}

export interface ReportSummaryData {
    grouped_by_category: CategoryReportItem[];
    grouped_by_domain: DomainReportItem[];
    grouped_by_dimension: DimensionReportItem[];
    final_risk_levels: FinalRiskLevel[];
    personalCalification: ParticipantScore[];
    detailed_results: DetailedResultCategory[];
}

export interface DemographicItem {
    name: string;
    total: number;
    risk_levels: RiskLevels;
    personal_by_risk: PersonalByRisk;
}

export interface DemographicSection {
    title: string;
    data: DemographicItem[];
}

export type DemographicDistribution = DemographicSection[];

export interface Tab {
    key: string;
    label: string;
}

export type RiskLevel = 'Nulo' | 'Bajo' | 'Medio' | 'Alto' | 'Muy Alto';

export const RISK_LEVELS: RiskLevel[] = ['Nulo', 'Bajo', 'Medio', 'Alto', 'Muy Alto'];

export interface ReportSummaryDashboardProps {
    organizations?: Array<{ id: string; name: string }>;
    isAdmin?: boolean;
    isSuperAdmin?: boolean;
    currentOrganization?: { id: string; name: string } | string | number | null;
}
