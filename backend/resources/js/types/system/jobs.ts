export interface ScheduledCommandLastExecution {
    status:       'success' | 'error'
    executed_at:  string
    output:       string | null
}

export interface ScheduledCommand {
    id:               string
    command:          string
    label:            string | null
    description:      string | null
    frequency:        string
    is_enabled:       boolean
    disabled_at:      string | null
    disabled_by:      string | null
    last_execution:   ScheduledCommandLastExecution | null
}

export interface PendingQueueSummary {
    queue:    string
    pending:  number
}

export interface FailedJob {
    uuid:               string
    connection:         string
    queue:               string
    job_name:           string
    exception_summary:  string
    failed_at:          string
}

export interface JobsCenterPageProps {
    scheduledCommands: ScheduledCommand[]
    pendingJobs:        PendingQueueSummary[]
    failedJobs:          FailedJob[]
}
