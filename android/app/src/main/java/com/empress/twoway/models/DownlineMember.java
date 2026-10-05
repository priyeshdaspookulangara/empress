package com.empress.twoway.models;

public class DownlineMember {
    private String memberId;
    private String sponsorId;
    private String name;
    private String packageType;
    private int level;

    public DownlineMember(String memberId, String sponsorId, String name, String packageType, int level) {
        this.memberId = memberId;
        this.sponsorId = sponsorId;
        this.name = name;
        this.packageType = packageType;
        this.level = level;
    }

    public String getMemberId() { return memberId; }
    public String getSponsorId() { return sponsorId; }
    public String getName() { return name; }
    public String getPackageType() { return packageType; }
    public int getLevel() { return level; }
}
