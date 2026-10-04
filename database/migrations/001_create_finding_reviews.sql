IF OBJECT_ID('dbo.finding_reviews', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.finding_reviews (
        review_id       INT IDENTITY(1,1) PRIMARY KEY,
        finding_id      INT           NOT NULL,
        approver_id     INT           NOT NULL,
        decision        VARCHAR(20)   NOT NULL,
        comment         NVARCHAR(1000) NULL,
        reviewed_at     DATETIME2     NOT NULL DEFAULT SYSDATETIME(),

        CONSTRAINT FK_finding_reviews_finding
            FOREIGN KEY (finding_id)
            REFERENCES dbo.findings(finding_id)
            ON DELETE CASCADE,

        CONSTRAINT FK_finding_reviews_approver
            FOREIGN KEY (approver_id)
            REFERENCES dbo.users(user_id),

        CONSTRAINT CK_finding_reviews_decision
            CHECK (decision IN ('APPROVED', 'REJECTED'))
    );

    CREATE INDEX IX_finding_reviews_finding
        ON dbo.finding_reviews(finding_id);
END;
GO