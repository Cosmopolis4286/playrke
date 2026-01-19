export interface BtnCardHeader {
    icon?: string
    route?: string
}

export interface CardHeaderPageProps {
    btnCardHeader?: BtnCardHeader
    returnBtn?: boolean
    subtitle?: string
    title: string
}
