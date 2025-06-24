CREATE TABLE [dbo].[Usuarios](
    [UsuarioID] [int] IDENTITY(1,1) NOT NULL,
    [Nome_Usuario] [nvarchar](100) NOT NULL,
    [Nome_completo] [varchar](70) NOT NULL,
    [CPF] [nvarchar](14) NOT NULL,  -- Alterado de Matricula para CPF
    [Senha] [varchar](50) NOT NULL,
    [DataCadastro] [datetime] NULL,
    [Ativo] [bit] NULL,
    CONSTRAINT [PK_Usuarios] PRIMARY KEY CLUSTERED ([UsuarioID] ASC)
);

CREATE TABLE [dbo].[DadosFaciais](
    [DadoFacialID] [int] IDENTITY(1,1) NOT NULL,
    [UsuarioID] [int] NOT NULL,
    [Descricao] [nvarchar](200) NULL,
    [VetorCaracteristicas] [varbinary](max) NOT NULL,
    [DataCadastro] [datetime] NULL,
    CONSTRAINT [PK_DadosFaciais] PRIMARY KEY CLUSTERED ([DadoFacialID] ASC),
    CONSTRAINT [FK_DadosFaciais_Usuarios] FOREIGN KEY ([UsuarioID]) 
        REFERENCES [dbo].[Usuarios] ([UsuarioID])
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE TABLE [dbo].[RegistrosAcesso](
    [RegistroID] [int] IDENTITY(1,1) NOT NULL,
    [UsuarioID] [int] NULL,
    [DataHora] [datetime] NULL,
    [Dispositivo] [nvarchar](100) NULL,
    [Localizacao] [nvarchar](100) NULL,
    [Sucesso] [bit] NOT NULL,
    [ImagemComparacao] [varbinary](max) NULL,
    CONSTRAINT [PK_RegistrosAcesso] PRIMARY KEY CLUSTERED ([RegistroID] ASC),
    CONSTRAINT [FK_RegistrosAcesso_Usuarios] FOREIGN KEY ([UsuarioID]) 
        REFERENCES [dbo].[Usuarios] ([UsuarioID])
        ON DELETE SET NULL
        ON UPDATE CASCADE
);