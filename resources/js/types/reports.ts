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
}

export interface DetailedResultDimension {
    nombre: string;
    items: DetailedResultItem[];
    rowspan: number;
}

export interface DetailedResultDomain {
    nombre: string;
    puntaje: number;
    dimensiones: DetailedResultDimension[];
    rowspan: number;
}

export interface DetailedResultCategory {
    nombre: string;
    puntaje: number;
    dominios: DetailedResultDomain[];
    rowspan: number;
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
